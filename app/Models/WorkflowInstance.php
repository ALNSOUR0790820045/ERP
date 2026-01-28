<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_definition_id',
        'entity_type',
        'entity_id',
        'current_step_id',
        'status',
        'started_at',
        'completed_at',
        'data',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'data' => 'array',
    ];

    // العلاقات
    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class);
    }

    public function entity()
    {
        return $this->morphTo();
    }

    // الثوابت
    public const STATUSES = [
        'pending' => 'معلق',
        'in_progress' => 'قيد التنفيذ',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
        'cancelled' => 'ملغي',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * معالجة إجراء على الخطوة الحالية
     */
    public function processAction(string $action, int $userId, ?string $comments = null): bool
    {
        $currentStep = $this->currentStep;
        
        if (!$currentStep) {
            return false;
        }

        // تسجيل الإجراء
        WorkflowAction::create([
            'workflow_instance_id' => $this->id,
            'workflow_step_id' => $currentStep->id,
            'user_id' => $userId,
            'action' => $action,
            'comments' => $comments,
            'action_date' => now(),
        ]);

        if ($action === 'approve') {
            // الانتقال للخطوة التالية
            if ($currentStep->is_final) {
                $this->status = 'approved';
                $this->completed_at = now();
            } else {
                $nextStep = $currentStep->onApproveStep ?? 
                    $this->workflowDefinition->steps()
                        ->where('step_order', '>', $currentStep->step_order)
                        ->first();
                        
                if ($nextStep) {
                    $this->current_step_id = $nextStep->id;
                } else {
                    $this->status = 'approved';
                    $this->completed_at = now();
                }
            }
        } elseif ($action === 'reject') {
            if ($currentStep->onRejectStep) {
                $this->current_step_id = $currentStep->on_reject_step_id;
            } else {
                $this->status = 'rejected';
                $this->completed_at = now();
            }
        }

        $this->save();

        // تحديث الكيان المرتبط
        $this->updateEntity();

        return true;
    }

    /**
     * تحديث الكيان المرتبط
     */
    protected function updateEntity(): void
    {
        $entity = $this->entity;
        
        if ($entity && method_exists($entity, 'updateFromWorkflow')) {
            $entity->updateFromWorkflow($this);
        }
    }

    /**
     * التحقق من صلاحية المستخدم للإجراء
     */
    public function canUserAct(int $userId): bool
    {
        $currentStep = $this->currentStep;
        
        if (!$currentStep) {
            return false;
        }

        $approvers = $currentStep->getApprovers($this->entity);
        
        return $approvers->contains('id', $userId);
    }
}
