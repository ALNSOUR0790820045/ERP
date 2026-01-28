<?php

namespace App\Models\DocumentManagement;

use App\Models\ProjectMeeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Meeting Action Item Model
 * بنود الإجراءات من الاجتماعات
 */
class MeetingActionItem extends Model
{
    protected $table = 'meeting_action_items';
    protected $fillable = [
        'meeting_id',
        'action_number',
        'title',
        'description',
        'assigned_to',
        'assigned_by',
        'priority',
        'status',
        'due_date',
        'completed_at',
        'completion_notes',
        'follow_up_required',
        'follow_up_date',
        'related_document_id',
        'category',
        'metadata',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'follow_up_date' => 'date',
        'follow_up_required' => 'boolean',
        'metadata' => 'array',
    ];

    // Status Constants
    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_DEFERRED = 'deferred';

    // Priority Constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    // Category Constants
    const CATEGORY_DESIGN = 'design';
    const CATEGORY_CONSTRUCTION = 'construction';
    const CATEGORY_DOCUMENTATION = 'documentation';
    const CATEGORY_COORDINATION = 'coordination';
    const CATEGORY_SAFETY = 'safety';
    const CATEGORY_QUALITY = 'quality';
    const CATEGORY_OTHER = 'other';

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(ProjectMeeting::class, 'meeting_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS]);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeOverdue($query)
    {
        return $query->pending()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->pending()
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_CRITICAL]);
    }

    // Helper Methods
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS]);
    }

    public function isOverdue(): bool
    {
        return $this->isPending() && $this->due_date && $this->due_date->isPast();
    }

    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) return 0;
        return $this->due_date->diffInDays(now());
    }

    public function getDaysRemaining(): int
    {
        if (!$this->due_date || $this->isCompleted()) return 0;
        return max(0, now()->diffInDays($this->due_date, false));
    }

    public function start(): void
    {
        $this->update(['status' => self::STATUS_IN_PROGRESS]);
    }

    public function complete(string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'completion_notes' => $notes,
        ]);
    }

    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completion_notes' => $reason,
        ]);
    }

    public function defer(?\Carbon\Carbon $newDueDate = null): void
    {
        $this->update([
            'status' => self::STATUS_DEFERRED,
            'due_date' => $newDueDate ?? $this->due_date->addDays(7),
        ]);
    }

    public function reopen(): void
    {
        $this->update([
            'status' => self::STATUS_OPEN,
            'completed_at' => null,
        ]);
    }

    public function requireFollowUp(\Carbon\Carbon $followUpDate): void
    {
        $this->update([
            'follow_up_required' => true,
            'follow_up_date' => $followUpDate,
        ]);
    }

    public static function generateNumber(ProjectMeeting $meeting): string
    {
        $count = static::where('meeting_id', $meeting->id)->count() + 1;
        return sprintf('AI-%s-%03d', $meeting->meeting_number ?? $meeting->id, $count);
    }

    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'success',
            self::STATUS_IN_PROGRESS => 'primary',
            self::STATUS_DEFERRED => 'warning',
            self::STATUS_CANCELLED => 'secondary',
            default => $this->isOverdue() ? 'danger' : 'info',
        };
    }

    public function getPriorityBadgeColor(): string
    {
        return match ($this->priority) {
            self::PRIORITY_CRITICAL => 'danger',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_MEDIUM => 'info',
            default => 'secondary',
        };
    }
}
