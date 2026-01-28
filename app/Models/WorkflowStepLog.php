<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سجل خطوة سير العمل
 * Workflow Step Log
 */
class WorkflowStepLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'instance_id',
        'step_id',
        'step_order',
        'status',
        'assigned_to',
        'acted_by',
        'action_taken',
        'comments',
        'attachments',
        'assigned_at',
        'due_at',
        'acted_at',
        'delegated_from',
        'delegation_reason',
    ];

    protected $casts = [
        'attachments' => 'array',
        'assigned_at' => 'datetime',
        'due_at' => 'datetime',
        'acted_at' => 'datetime',
    ];

    // الحالات
    const STATUSES = [
        'pending' => 'معلق',
        'in_progress' => 'قيد التنفيذ',
        'approved' => 'موافق',
        'rejected' => 'مرفوض',
        'skipped' => 'تم تخطيه',
        'delegated' => 'مفوض',
        'returned' => 'مرتجع',
        'expired' => 'منتهي',
    ];

    // الإجراءات
    const ACTIONS = [
        'approve' => 'موافقة',
        'reject' => 'رفض',
        'return' => 'إرجاع',
        'delegate' => 'تفويض',
        'request_info' => 'طلب معلومات',
    ];

    // العلاقات
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'instance_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'step_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }

    public function delegatedFrom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_from');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'in_progress' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            'skipped' => 'gray',
            'delegated' => 'info',
            'returned' => 'warning',
            'expired' => 'danger',
            default => 'gray',
        };
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTIONS[$this->action_taken] ?? $this->action_taken;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' 
            && $this->due_at 
            && $this->due_at->isPast();
    }

    // Methods
    /**
     * تنفيذ إجراء
     */
    public function executeAction(string $action, ?string $comments = null, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        
        $this->action_taken = $action;
        $this->comments = $comments;
        $this->acted_by = $user->id;
        $this->acted_at = now();
        
        switch ($action) {
            case 'approve':
                $this->status = 'approved';
                $this->save();
                $this->instance->moveToNextStep();
                break;

            case 'reject':
                $this->status = 'rejected';
                $this->save();
                $this->instance->complete('rejected', $comments);
                break;

            case 'return':
                $this->status = 'returned';
                $this->save();
                $this->instance->complete('returned', $comments);
                break;

            default:
                $this->save();
        }

        // إرسال إشعار
        $this->sendNotification($action);

        return true;
    }

    /**
     * تفويض الخطوة
     */
    public function delegate(User $delegateTo, string $reason): bool
    {
        $this->delegated_from = $this->assigned_to;
        $this->delegation_reason = $reason;
        $this->assigned_to = $delegateTo->id;
        $this->status = 'delegated';
        
        $saved = $this->save();

        // إنشاء سجل جديد للمفوض إليه
        if ($saved) {
            self::create([
                'instance_id' => $this->instance_id,
                'step_id' => $this->step_id,
                'step_order' => $this->step_order,
                'status' => 'pending',
                'assigned_to' => $delegateTo->id,
                'assigned_at' => now(),
                'due_at' => $this->due_at,
                'delegated_from' => $this->delegated_from,
                'delegation_reason' => $reason,
            ]);
        }

        return $saved;
    }

    /**
     * إرسال إشعار
     */
    protected function sendNotification(string $action): void
    {
        // TODO: تنفيذ نظام الإشعارات
    }
}
