<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAssignment extends Model
{
    protected $fillable = [
        'workflow_instance_id',
        'workflow_step_id',
        'assigned_to_user_id',
        'assigned_by_user_id',
        'status',
        'assigned_at',
        'accepted_at',
        'completed_at',
        'due_at',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'completed_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    // الحالات
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ESCALATED = 'escalated';

    // العلاقات
    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    // الدوال المساعدة
    public function accept(): void
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    public function complete(string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'notes' => $notes,
        ]);
    }

    public function reject(string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'notes' => $notes,
        ]);
    }

    public function escalate(): void
    {
        $this->update([
            'status' => self::STATUS_ESCALATED,
        ]);
    }

    public function isOverdue(): bool
    {
        return $this->due_at && now()->isAfter($this->due_at) && $this->status === self::STATUS_PENDING;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('assigned_to_user_id', $userId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }
}
