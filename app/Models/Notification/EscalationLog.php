<?php

namespace App\Models\Notification;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

class EscalationLog extends Model
{
    protected $table = 'escalation_logs';

    protected $fillable = [
        'rule_id',
        'escalatable_type',
        'escalatable_id',
        'current_level',
        'escalated_to_user_id',
        'escalated_to_role_id',
        'status',
        'escalated_at',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'metadata',
    ];

    protected $casts = [
        'escalated_at' => 'datetime',
        'resolved_at' => 'datetime',
        'metadata' => 'array',
    ];

    // العلاقات
    public function rule(): BelongsTo
    {
        return $this->belongsTo(EscalationRule::class, 'rule_id');
    }

    public function escalatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function escalatedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to_user_id');
    }

    public function escalatedToRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'escalated_to_role_id');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'escalated');
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'resolved');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'expired');
    }

    // حل التصعيد
    public function resolve(int $resolvedBy, string $notes): bool
    {
        return $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy,
            'resolution_notes' => $notes,
        ]);
    }

    // إلغاء التصعيد
    public function cancel(string $notes = null): bool
    {
        return $this->update([
            'status' => 'cancelled',
            'resolution_notes' => $notes,
        ]);
    }

    // تصعيد للمستوى التالي
    public function escalateToNextLevel(): bool
    {
        $nextLevel = $this->rule->getNextLevel($this->current_level);

        if (!$nextLevel) {
            return $this->update(['status' => 'expired']);
        }

        return $this->update([
            'current_level' => $nextLevel->level,
            'escalated_to_user_id' => $nextLevel->escalate_to_user_id,
            'escalated_to_role_id' => $nextLevel->escalate_to_role_id,
            'escalated_at' => now(),
        ]);
    }

    // هل يحتاج تصعيد؟
    public function needsEscalation(): bool
    {
        if ($this->status !== 'escalated') return false;

        $level = $this->rule->levels()->where('level', $this->current_level)->first();
        if (!$level) return false;

        return $level->shouldEscalate($this->escalated_at);
    }
}
