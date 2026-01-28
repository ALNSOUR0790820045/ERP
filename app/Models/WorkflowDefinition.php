<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'entity_type',
        'trigger_event',
        'conditions',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('step_order');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    // الثوابت
    public const TRIGGER_EVENTS = [
        'created' => 'عند الإنشاء',
        'updated' => 'عند التحديث',
        'submitted' => 'عند التقديم',
        'amount_exceeds' => 'عند تجاوز المبلغ',
    ];

    public const ENTITY_TYPES = [
        'App\\Models\\PurchaseRequest' => 'طلبات الشراء',
        'App\\Models\\PurchaseOrder' => 'أوامر الشراء',
        'App\\Models\\PaymentVoucher' => 'سندات الصرف',
        'App\\Models\\ProgressCertificate' => 'شهادات الإنجاز',
        'App\\Models\\LeaveRequest' => 'طلبات الإجازة',
        'App\\Models\\TenderSubmission' => 'عروض المناقصات',
    ];

    /**
     * بدء سير عمل جديد
     */
    public function startWorkflow($entity): ?WorkflowInstance
    {
        if (!$this->is_active) {
            return null;
        }

        // التحقق من الشروط
        if (!$this->checkConditions($entity)) {
            return null;
        }

        $instance = WorkflowInstance::create([
            'workflow_definition_id' => $this->id,
            'entity_type' => get_class($entity),
            'entity_id' => $entity->id,
            'current_step_id' => $this->steps()->first()?->id,
            'status' => 'pending',
            'started_at' => now(),
        ]);

        return $instance;
    }

    /**
     * التحقق من شروط سير العمل
     */
    protected function checkConditions($entity): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            if (!$field) continue;

            $entityValue = $entity->$field ?? null;

            switch ($operator) {
                case '=':
                    if ($entityValue != $value) return false;
                    break;
                case '>':
                    if ($entityValue <= $value) return false;
                    break;
                case '<':
                    if ($entityValue >= $value) return false;
                    break;
                case '>=':
                    if ($entityValue < $value) return false;
                    break;
                case '<=':
                    if ($entityValue > $value) return false;
                    break;
            }
        }

        return true;
    }
}
