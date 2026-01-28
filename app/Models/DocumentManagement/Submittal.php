<?php

namespace App\Models\DocumentManagement;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Submittal Model
 * نظام التسليمات ورسومات الورشة (Shop Drawings)
 */
class Submittal extends Model
{
    protected $fillable = [
        'project_id',
        'submittal_number',
        'title',
        'description',
        'spec_section',
        'submittal_type',
        'priority',
        'status',
        'submitted_by',
        'submitted_at',
        'required_date',
        'approved_date',
        'current_revision',
        'contractor_id',
        'subcontractor_id',
        'discipline',
        'lead_time_days',
        'is_closed',
        'closed_at',
        'metadata',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'required_date' => 'date',
        'approved_date' => 'date',
        'closed_at' => 'datetime',
        'is_closed' => 'boolean',
        'lead_time_days' => 'integer',
        'metadata' => 'array',
    ];

    // Status Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_APPROVED_AS_NOTED = 'approved_as_noted';
    const STATUS_REVISE_RESUBMIT = 'revise_resubmit';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CLOSED = 'closed';

    // Type Constants
    const TYPE_SHOP_DRAWING = 'shop_drawing';
    const TYPE_PRODUCT_DATA = 'product_data';
    const TYPE_SAMPLE = 'sample';
    const TYPE_MOCKUP = 'mockup';
    const TYPE_CALCULATION = 'calculation';
    const TYPE_CERTIFICATE = 'certificate';

    // Priority Constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contractor_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(SubmittalRevision::class);
    }

    public function reviewCycles(): HasMany
    {
        return $this->hasMany(SubmittalReviewCycle::class);
    }

    // Scopes
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }

    public function scopeClosed($query)
    {
        return $query->where('is_closed', true);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
        ]);
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', [
            self::STATUS_APPROVED,
            self::STATUS_APPROVED_AS_NOTED,
        ]);
    }

    public function scopeOverdue($query)
    {
        return $query->open()
            ->whereNotNull('required_date')
            ->where('required_date', '<', now());
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('submittal_type', $type);
    }

    // Helper Methods
    public function isApproved(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_APPROVED_AS_NOTED,
        ]);
    }

    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
        ]);
    }

    public function isOverdue(): bool
    {
        return !$this->is_closed && $this->required_date && $this->required_date->isPast();
    }

    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) return 0;
        return $this->required_date->diffInDays(now());
    }

    public function getCurrentRevision(): ?SubmittalRevision
    {
        return $this->revisions()
            ->where('revision_number', $this->current_revision)
            ->first();
    }

    public function getLatestRevision(): ?SubmittalRevision
    {
        return $this->revisions()->latest('revision_number')->first();
    }

    public function submit(): void
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    public function approve(string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_date' => now()->toDateString(),
        ]);
    }

    public function approveAsNoted(string $notes): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED_AS_NOTED,
            'approved_date' => now()->toDateString(),
        ]);
    }

    public function requestRevision(string $comments): void
    {
        $this->update([
            'status' => self::STATUS_REVISE_RESUBMIT,
        ]);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
        ]);
    }

    public function close(): void
    {
        $this->update([
            'is_closed' => true,
            'closed_at' => now(),
            'status' => self::STATUS_CLOSED,
        ]);
    }

    public static function generateNumber(Project $project): string
    {
        $count = static::forProject($project->id)->count() + 1;
        return sprintf('SUB-%s-%04d', $project->code ?? $project->id, $count);
    }

    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'success',
            self::STATUS_APPROVED_AS_NOTED => 'info',
            self::STATUS_REVISE_RESUBMIT => 'warning',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_UNDER_REVIEW => 'primary',
            default => 'secondary',
        };
    }
}
