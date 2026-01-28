<?php

namespace App\Models\DocumentManagement;

use App\Models\ProjectMeeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Meeting Decision Model
 * قرارات الاجتماعات
 */
class MeetingDecision extends Model
{
    protected $table = 'meeting_decisions';
    protected $fillable = [
        'meeting_id',
        'decision_number',
        'title',
        'description',
        'decision_type',
        'category',
        'made_by',
        'approved_by',
        'status',
        'effective_date',
        'expiry_date',
        'impact_level',
        'stakeholders',
        'supporting_documents',
        'rationale',
        'alternatives_considered',
        'implementation_notes',
        'is_confidential',
        'requires_follow_up',
        'follow_up_date',
        'metadata',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'follow_up_date' => 'date',
        'stakeholders' => 'array',
        'supporting_documents' => 'array',
        'alternatives_considered' => 'array',
        'is_confidential' => 'boolean',
        'requires_follow_up' => 'boolean',
        'metadata' => 'array',
    ];

    // Status Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_IMPLEMENTED = 'implemented';
    const STATUS_SUPERSEDED = 'superseded';
    const STATUS_CANCELLED = 'cancelled';

    // Decision Type Constants
    const TYPE_DESIGN = 'design';
    const TYPE_TECHNICAL = 'technical';
    const TYPE_FINANCIAL = 'financial';
    const TYPE_SCHEDULE = 'schedule';
    const TYPE_CONTRACTUAL = 'contractual';
    const TYPE_CHANGE_ORDER = 'change_order';
    const TYPE_SAFETY = 'safety';
    const TYPE_OTHER = 'other';

    // Impact Level Constants
    const IMPACT_LOW = 'low';
    const IMPACT_MEDIUM = 'medium';
    const IMPACT_HIGH = 'high';
    const IMPACT_CRITICAL = 'critical';

    // Category Constants
    const CATEGORY_DESIGN = 'design';
    const CATEGORY_CONSTRUCTION = 'construction';
    const CATEGORY_PROCUREMENT = 'procurement';
    const CATEGORY_QUALITY = 'quality';
    const CATEGORY_SAFETY = 'safety';
    const CATEGORY_COORDINATION = 'coordination';

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(ProjectMeeting::class, 'meeting_id');
    }

    public function madeBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'made_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeImplemented($query)
    {
        return $query->where('status', self::STATUS_IMPLEMENTED);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_IMPLEMENTED])
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', now());
            });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('decision_type', $type);
    }

    public function scopeHighImpact($query)
    {
        return $query->whereIn('impact_level', [self::IMPACT_HIGH, self::IMPACT_CRITICAL]);
    }

    public function scopeRequiringFollowUp($query)
    {
        return $query->where('requires_follow_up', true)
            ->whereNotNull('follow_up_date')
            ->where('follow_up_date', '<=', now()->addDays(7));
    }

    public function scopeConfidential($query)
    {
        return $query->where('is_confidential', true);
    }

    // Helper Methods
    public function isApproved(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_IMPLEMENTED]);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function isActive(): bool
    {
        if (!$this->isApproved()) return false;
        if ($this->expiry_date && $this->expiry_date->isPast()) return false;
        return true;
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function approve(User $approver): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approver->id,
        ]);
    }

    public function implement(string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_IMPLEMENTED,
            'implementation_notes' => $notes,
        ]);
    }

    public function supersede(self $newDecision): void
    {
        $this->update([
            'status' => self::STATUS_SUPERSEDED,
            'metadata' => array_merge($this->metadata ?? [], [
                'superseded_by' => $newDecision->id,
                'superseded_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'metadata' => array_merge($this->metadata ?? [], [
                'cancellation_reason' => $reason,
                'cancelled_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    public function addStakeholder(int $userId, string $role = null): void
    {
        $stakeholders = $this->stakeholders ?? [];
        $stakeholders[] = [
            'user_id' => $userId,
            'role' => $role,
            'added_at' => now()->toIso8601String(),
        ];
        $this->update(['stakeholders' => $stakeholders]);
    }

    public function attachDocument(int $documentId, string $type = 'supporting'): void
    {
        $documents = $this->supporting_documents ?? [];
        $documents[] = [
            'document_id' => $documentId,
            'type' => $type,
            'attached_at' => now()->toIso8601String(),
        ];
        $this->update(['supporting_documents' => $documents]);
    }

    public static function generateNumber(ProjectMeeting $meeting): string
    {
        $count = static::where('meeting_id', $meeting->id)->count() + 1;
        return sprintf('DEC-%s-%03d', $meeting->meeting_number ?? $meeting->id, $count);
    }

    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_IMPLEMENTED => 'success',
            self::STATUS_APPROVED => 'primary',
            self::STATUS_PENDING_APPROVAL => 'warning',
            self::STATUS_SUPERSEDED => 'secondary',
            self::STATUS_CANCELLED => 'danger',
            default => 'info',
        };
    }

    public function getImpactBadgeColor(): string
    {
        return match ($this->impact_level) {
            self::IMPACT_CRITICAL => 'danger',
            self::IMPACT_HIGH => 'warning',
            self::IMPACT_MEDIUM => 'info',
            default => 'secondary',
        };
    }
}
