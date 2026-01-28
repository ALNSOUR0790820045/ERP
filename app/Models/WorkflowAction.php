<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_instance_id',
        'workflow_step_id',
        'user_id',
        'action',
        'comments',
        'action_date',
        'ip_address',
    ];

    protected $casts = [
        'action_date' => 'datetime',
    ];

    // العلاقات
    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // الثوابت
    public const ACTIONS = [
        'approve' => 'موافقة',
        'reject' => 'رفض',
        'return' => 'إرجاع',
        'delegate' => 'تفويض',
        'escalate' => 'تصعيد',
        'comment' => 'تعليق',
    ];

    public function getActionLabelAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->ip_address = request()->ip();
        });
    }
}
