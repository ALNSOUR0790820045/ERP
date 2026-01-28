<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_definition_id',
        'step_order',
        'name',
        'description',
        'approver_type',
        'approver_id',
        'approver_role',
        'approval_type',
        'time_limit_hours',
        'escalation_step_id',
        'on_approve_step_id',
        'on_reject_step_id',
        'conditions',
        'is_final',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_final' => 'boolean',
    ];

    // العلاقات
    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function escalationStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'escalation_step_id');
    }

    public function onApproveStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'on_approve_step_id');
    }

    public function onRejectStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'on_reject_step_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class);
    }

    // الثوابت
    public const APPROVER_TYPES = [
        'user' => 'مستخدم محدد',
        'role' => 'دور/صلاحية',
        'manager' => 'المدير المباشر',
        'department_head' => 'رئيس القسم',
    ];

    public const APPROVAL_TYPES = [
        'single' => 'موافقة واحدة',
        'all' => 'جميع المعتمدين',
        'majority' => 'الأغلبية',
    ];

    /**
     * الحصول على المعتمدين لهذه الخطوة
     */
    public function getApprovers($entity = null): \Illuminate\Support\Collection
    {
        switch ($this->approver_type) {
            case 'user':
                return collect([$this->approver]);
                
            case 'role':
                return User::role($this->approver_role)->get();
                
            case 'manager':
                if ($entity && method_exists($entity, 'getManager')) {
                    return collect([$entity->getManager()]);
                }
                return collect();
                
            case 'department_head':
                if ($entity && method_exists($entity, 'getDepartmentHead')) {
                    return collect([$entity->getDepartmentHead()]);
                }
                return collect();
                
            default:
                return collect();
        }
    }
}
