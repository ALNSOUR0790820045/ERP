<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowStepExecution extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workflow_instance_id',
        'workflow_step_id',
        'assigned_to',
        'delegated_by',
        'status', // pending, approved, rejected, skipped, escalated, cancelled, delegated
        'action', // approve, reject, delegate, reassign
        'started_at',
        'completed_at',
        'due_at',
        'escalated_at',
        'completed_by',
        'comments',
        'form_data', // بيانات النموذج المطلوب
        'attachments',
        'signature', // التوقيع الإلكتروني
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'due_at' => 'datetime',
        'escalated_at' => 'datetime',
        'form_data' => 'array',
        'attachments' => 'array',
        'signature' => 'array',
        'metadata' => 'array',
    ];

    // Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_SKIPPED = 'skipped';
    const STATUS_ESCALATED = 'escalated';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_DELEGATED = 'delegated';

    // Action Constants
    const ACTION_APPROVE = 'approve';
    const ACTION_REJECT = 'reject';
    const ACTION_DELEGATE = 'delegate';
    const ACTION_REASSIGN = 'reassign';
    const ACTION_REQUEST_INFO = 'request_info';

    // ===== العلاقات =====

    public function instance()
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function step()
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function delegatedBy()
    {
        return $this->belongsTo(User::class, 'delegated_by');
    }

    // ===== Scopes =====

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_REJECTED]);
    }

    public function scopeOverdue($query)
    {
        return $query->pending()
                     ->whereNotNull('due_at')
                     ->where('due_at', '<', now());
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeNeedsEscalation($query)
    {
        return $query->pending()
                     ->whereHas('step', function ($q) {
                         $q->where('escalation_enabled', true);
                     })
                     ->where(function ($q) {
                         $q->where('due_at', '<', now());
                     });
    }

    // ===== Accessors =====

    public function getStatusNameAttribute()
    {
        $statuses = [
            'pending' => 'في الانتظار',
            'approved' => 'موافق عليه',
            'rejected' => 'مرفوض',
            'skipped' => 'تم تخطيه',
            'escalated' => 'تم التصعيد',
            'cancelled' => 'ملغي',
            'delegated' => 'تم التفويض',
        ];
        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'skipped' => 'gray',
            'escalated' => 'warning',
            'cancelled' => 'gray',
            'delegated' => 'info',
        ];
        return $colors[$this->status] ?? 'gray';
    }

    public function getActionNameAttribute()
    {
        $actions = [
            'approve' => 'موافقة',
            'reject' => 'رفض',
            'delegate' => 'تفويض',
            'reassign' => 'إعادة تعيين',
            'request_info' => 'طلب معلومات',
        ];
        return $actions[$this->action] ?? $this->action;
    }

    public function getIsOverdueAttribute()
    {
        return $this->due_at && 
               $this->due_at < now() && 
               $this->status === self::STATUS_PENDING;
    }

    public function getDurationHoursAttribute()
    {
        $endTime = $this->completed_at ?? now();
        return $this->started_at ? $this->started_at->diffInHours($endTime) : 0;
    }

    public function getTimeToDeadlineAttribute()
    {
        if (!$this->due_at || $this->status !== self::STATUS_PENDING) {
            return null;
        }
        return now()->diffInHours($this->due_at, false);
    }

    // ===== Methods =====

    /**
     * موافقة
     */
    public function approve(?string $comment = null, ?array $formData = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'action' => self::ACTION_APPROVE,
            'status' => self::STATUS_APPROVED,
            'completed_at' => now(),
            'completed_by' => auth()->id(),
            'comments' => $comment,
            'form_data' => $formData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // إعلام Instance للانتقال للخطوة التالية
        $this->instance->processStepAction($this, 'approve', $comment, $formData);

        return true;
    }

    /**
     * رفض
     */
    public function reject(?string $comment = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        if ($this->step->require_comment && !$comment) {
            return false;
        }

        $this->update([
            'action' => self::ACTION_REJECT,
            'status' => self::STATUS_REJECTED,
            'completed_at' => now(),
            'completed_by' => auth()->id(),
            'comments' => $comment,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $this->instance->processStepAction($this, 'reject', $comment);

        return true;
    }

    /**
     * تفويض لمستخدم آخر
     */
    public function delegate($toUserId, ?string $reason = null): bool
    {
        if (!$this->step->can_delegate) {
            return false;
        }

        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        // تحديث هذا التنفيذ
        $this->update([
            'status' => self::STATUS_DELEGATED,
            'completed_at' => now(),
            'comments' => $reason,
        ]);

        // إنشاء تنفيذ جديد للمستخدم المفوض
        $this->instance->stepExecutions()->create([
            'workflow_step_id' => $this->workflow_step_id,
            'assigned_to' => $toUserId,
            'delegated_by' => auth()->id(),
            'status' => self::STATUS_PENDING,
            'started_at' => now(),
            'due_at' => $this->due_at,
            'metadata' => [
                'delegated_from' => $this->id,
                'delegation_reason' => $reason,
            ],
        ]);

        return true;
    }

    /**
     * إعادة تعيين
     */
    public function reassign($toUserId, ?string $reason = null): bool
    {
        if (!$this->step->can_reassign) {
            return false;
        }

        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'assigned_to' => $toUserId,
            'metadata' => array_merge($this->metadata ?? [], [
                'reassigned_from' => $this->getOriginal('assigned_to'),
                'reassigned_at' => now()->toISOString(),
                'reassignment_reason' => $reason,
            ]),
        ]);

        return true;
    }

    /**
     * تصعيد
     */
    public function escalate(): bool
    {
        if (!$this->step->escalation_enabled) {
            return false;
        }

        $escalateTo = $this->step->escalation_to;
        if (!$escalateTo) {
            // التصعيد للمدير
            $escalateTo = $this->assignedTo?->manager_id;
        }

        if (!$escalateTo) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_ESCALATED,
            'escalated_at' => now(),
        ]);

        // إنشاء تنفيذ للمصعد إليه
        $this->instance->stepExecutions()->create([
            'workflow_step_id' => $this->workflow_step_id,
            'assigned_to' => $escalateTo,
            'status' => self::STATUS_PENDING,
            'started_at' => now(),
            'due_at' => now()->addHours($this->step->escalation_hours ?? 24),
            'metadata' => [
                'escalated_from' => $this->id,
                'original_assignee' => $this->assigned_to,
            ],
        ]);

        return true;
    }

    /**
     * إضافة توقيع إلكتروني
     */
    public function addSignature(string $signatureData, ?string $signatureType = 'draw'): void
    {
        $this->update([
            'signature' => [
                'data' => $signatureData,
                'type' => $signatureType,
                'signed_at' => now()->toISOString(),
                'signed_by' => auth()->id(),
                'ip_address' => request()->ip(),
            ],
        ]);
    }

    /**
     * إضافة مرفق
     */
    public function addAttachment(string $path, string $name, ?string $type = null): void
    {
        $attachments = $this->attachments ?? [];
        $attachments[] = [
            'path' => $path,
            'name' => $name,
            'type' => $type,
            'uploaded_at' => now()->toISOString(),
            'uploaded_by' => auth()->id(),
        ];
        $this->update(['attachments' => $attachments]);
    }

    /**
     * التحقق من إمكانية التنفيذ
     */
    public function canBeActedUponBy($userId): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        return $this->assigned_to === $userId;
    }

    /**
     * الحصول على ملخص التنفيذ
     */
    public function getSummary(): array
    {
        return [
            'step_name' => $this->step->name,
            'assigned_to' => $this->assignedTo?->name,
            'status' => $this->status_name,
            'action' => $this->action_name,
            'started_at' => $this->started_at?->format('Y-m-d H:i'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i'),
            'duration_hours' => $this->duration_hours,
            'is_overdue' => $this->is_overdue,
            'comments' => $this->comments,
        ];
    }
}
