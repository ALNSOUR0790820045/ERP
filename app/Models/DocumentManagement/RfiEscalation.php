<?php

namespace App\Models\DocumentManagement;

use App\Models\Rfi;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RFI Escalation Model
 * تصعيد طلبات المعلومات
 */
class RfiEscalation extends Model
{
    protected $fillable = [
        'rfi_id',
        'escalation_level',
        'escalation_reason',
        'escalated_from',
        'escalated_to',
        'escalation_notes',
        'new_due_date',
        'status',
        'escalated_at',
        'resolved_at',
        'resolution_notes',
        'is_auto',
        'metadata',
    ];

    protected $casts = [
        'new_due_date' => 'date',
        'escalated_at' => 'datetime',
        'resolved_at' => 'datetime',
        'is_auto' => 'boolean',
        'metadata' => 'array',
        'escalation_level' => 'integer',
    ];

    // Status Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CANCELLED = 'cancelled';

    // Escalation Reasons
    const REASON_OVERDUE = 'overdue';
    const REASON_NO_RESPONSE = 'no_response';
    const REASON_URGENT = 'urgent';
    const REASON_COMPLEXITY = 'complexity';
    const REASON_MANUAL = 'manual';

    public function rfi(): BelongsTo
    {
        return $this->belongsTo(Rfi::class);
    }

    public function escalatedFrom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_from');
    }

    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeAutomatic($query)
    {
        return $query->where('is_auto', true);
    }

    public function scopeManual($query)
    {
        return $query->where('is_auto', false);
    }

    public function scopeByLevel($query, int $level)
    {
        return $query->where('escalation_level', $level);
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isAutomatic(): bool
    {
        return $this->is_auto === true;
    }

    public function resolve(string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);

        // Update RFI escalation level
        $this->rfi->update([
            'current_escalation_level' => 0,
        ]);
    }

    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'resolved_at' => now(),
            'resolution_notes' => $reason ?? 'Cancelled',
        ]);
    }

    public function getTimeToResolve(): ?int
    {
        if (!$this->resolved_at) return null;
        return $this->escalated_at->diffInHours($this->resolved_at);
    }

    public static function escalateRfi(Rfi $rfi, User $escalateTo, string $reason, bool $isAuto = false): self
    {
        $currentLevel = $rfi->current_escalation_level ?? 0;
        $newLevel = $currentLevel + 1;

        // Update RFI
        $rfi->update([
            'current_escalation_level' => $newLevel,
            'last_escalated_at' => now(),
        ]);

        // Create escalation record
        return static::create([
            'rfi_id' => $rfi->id,
            'escalation_level' => $newLevel,
            'escalation_reason' => $reason,
            'escalated_from' => $rfi->assigned_to,
            'escalated_to' => $escalateTo->id,
            'status' => self::STATUS_ACTIVE,
            'escalated_at' => now(),
            'is_auto' => $isAuto,
            'new_due_date' => now()->addDays(2),
        ]);
    }

    public static function autoEscalateOverdueRfis(): array
    {
        $escalated = [];
        
        $overdueRfis = Rfi::where('status', 'open')
            ->where('auto_escalate', true)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->whereRaw('current_escalation_level < 3')
            ->get();

        foreach ($overdueRfis as $rfi) {
            $escalateTo = static::getNextEscalationUser($rfi);
            if ($escalateTo) {
                $escalated[] = static::escalateRfi(
                    $rfi,
                    $escalateTo,
                    self::REASON_OVERDUE,
                    true
                );
            }
        }

        return $escalated;
    }

    protected static function getNextEscalationUser(Rfi $rfi): ?User
    {
        // Get project manager or next level supervisor
        $level = ($rfi->current_escalation_level ?? 0) + 1;

        // This would need to be customized based on organization structure
        // For now, return the project's manager if available
        if ($rfi->project && $rfi->project->manager_id) {
            return User::find($rfi->project->manager_id);
        }

        return null;
    }
}
