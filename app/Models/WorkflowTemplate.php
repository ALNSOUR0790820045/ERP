<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'category', // purchase, sales, finance, project, hr, general
        'model_type', // App\Models\PurchaseOrder, etc.
        'trigger_event', // created, updated, status_changed, amount_exceeded
        'trigger_conditions',
        'is_active',
        'is_default',
        'version',
        'priority',
        'sla_hours', // الوقت المتوقع للإكمال
        'escalation_enabled',
        'escalation_hours',
        'notification_settings',
        'settings',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'notification_settings' => 'array',
        'settings' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'escalation_enabled' => 'boolean',
    ];

    // Categories
    const CATEGORY_PURCHASE = 'purchase';
    const CATEGORY_SALES = 'sales';
    const CATEGORY_FINANCE = 'finance';
    const CATEGORY_PROJECT = 'project';
    const CATEGORY_HR = 'hr';
    const CATEGORY_GENERAL = 'general';

    // Trigger Events
    const TRIGGER_CREATED = 'created';
    const TRIGGER_UPDATED = 'updated';
    const TRIGGER_STATUS_CHANGED = 'status_changed';
    const TRIGGER_AMOUNT_EXCEEDED = 'amount_exceeded';
    const TRIGGER_SUBMITTED = 'submitted';
    const TRIGGER_MANUAL = 'manual';

    // ===== العلاقات =====

    public function steps()
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('order');
    }

    public function instances()
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ===== Scopes =====

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeForModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ===== Accessors =====

    public function getCategoryNameAttribute()
    {
        $categories = [
            'purchase' => 'المشتريات',
            'sales' => 'المبيعات',
            'finance' => 'المالية',
            'project' => 'المشاريع',
            'hr' => 'الموارد البشرية',
            'general' => 'عام',
        ];
        return $categories[$this->category] ?? $this->category;
    }

    public function getTriggerEventNameAttribute()
    {
        $events = [
            'created' => 'عند الإنشاء',
            'updated' => 'عند التحديث',
            'status_changed' => 'عند تغيير الحالة',
            'amount_exceeded' => 'عند تجاوز المبلغ',
            'submitted' => 'عند التقديم',
            'manual' => 'يدوي',
        ];
        return $events[$this->trigger_event] ?? $this->trigger_event;
    }

    public function getStepsCountAttribute()
    {
        return $this->steps()->count();
    }

    public function getActiveInstancesCountAttribute()
    {
        return $this->instances()->active()->count();
    }

    // ===== Methods =====

    /**
     * بدء تنفيذ الـ Workflow
     */
    public function start(Model $model, $initiatorId = null): WorkflowInstance
    {
        $instance = $this->instances()->create([
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'status' => WorkflowInstance::STATUS_IN_PROGRESS,
            'current_step' => 1,
            'started_at' => now(),
            'initiated_by' => $initiatorId ?? auth()->id(),
            'due_at' => $this->sla_hours ? now()->addHours($this->sla_hours) : null,
            'context' => $this->buildContext($model),
        ]);

        // بدء الخطوة الأولى
        $firstStep = $this->steps()->where('order', 1)->first();
        if ($firstStep) {
            $instance->executeStep($firstStep);
        }

        return $instance;
    }

    /**
     * بناء السياق من الموديل
     */
    protected function buildContext(Model $model): array
    {
        $context = $model->toArray();
        
        // إضافة معلومات إضافية حسب النوع
        if (method_exists($model, 'getWorkflowContext')) {
            $context = array_merge($context, $model->getWorkflowContext());
        }

        return $context;
    }

    /**
     * التحقق من شروط التفعيل
     */
    public function shouldTrigger(Model $model, string $event): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->trigger_event !== $event && $this->trigger_event !== self::TRIGGER_MANUAL) {
            return false;
        }

        // التحقق من الشروط الإضافية
        if ($this->trigger_conditions) {
            return $this->evaluateConditions($model, $this->trigger_conditions);
        }

        return true;
    }

    /**
     * تقييم الشروط
     */
    protected function evaluateConditions(Model $model, array $conditions): bool
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            if (!$field) continue;

            $modelValue = data_get($model, $field);

            switch ($operator) {
                case '=':
                case '==':
                    if ($modelValue != $value) return false;
                    break;
                case '!=':
                case '<>':
                    if ($modelValue == $value) return false;
                    break;
                case '>':
                    if ($modelValue <= $value) return false;
                    break;
                case '>=':
                    if ($modelValue < $value) return false;
                    break;
                case '<':
                    if ($modelValue >= $value) return false;
                    break;
                case '<=':
                    if ($modelValue > $value) return false;
                    break;
                case 'in':
                    if (!in_array($modelValue, (array)$value)) return false;
                    break;
                case 'not_in':
                    if (in_array($modelValue, (array)$value)) return false;
                    break;
            }
        }

        return true;
    }

    /**
     * استنساخ القالب
     */
    public function duplicate(): self
    {
        $clone = $this->replicate();
        $clone->name = $this->name . ' (نسخة)';
        $clone->code = $this->code . '_copy_' . time();
        $clone->is_default = false;
        $clone->version = 1;
        $clone->save();

        // نسخ الخطوات
        foreach ($this->steps as $step) {
            $stepClone = $step->replicate();
            $stepClone->workflow_template_id = $clone->id;
            $stepClone->save();
        }

        return $clone;
    }

    /**
     * إنشاء نسخة جديدة
     */
    public function createNewVersion(): self
    {
        $newVersion = $this->duplicate();
        $newVersion->name = $this->name;
        $newVersion->code = $this->code;
        $newVersion->version = $this->version + 1;
        $newVersion->save();

        // إلغاء تفعيل النسخة القديمة
        $this->update(['is_active' => false]);

        return $newVersion;
    }

    /**
     * الحصول على إحصائيات الـ Workflow
     */
    public function getStatistics(): array
    {
        $instances = $this->instances();
        
        return [
            'total_instances' => $instances->count(),
            'active_instances' => $instances->active()->count(),
            'completed_instances' => $instances->completed()->count(),
            'rejected_instances' => $instances->rejected()->count(),
            'avg_completion_hours' => $instances->completed()
                ->avg(\DB::raw('TIMESTAMPDIFF(HOUR, started_at, completed_at)')),
            'on_time_percentage' => $this->calculateOnTimePercentage(),
        ];
    }

    /**
     * حساب نسبة الإنجاز في الوقت
     */
    protected function calculateOnTimePercentage(): float
    {
        $completed = $this->instances()->completed()->count();
        if ($completed === 0) return 0;

        $onTime = $this->instances()->completed()
            ->whereRaw('completed_at <= due_at')
            ->count();

        return round(($onTime / $completed) * 100, 2);
    }

    /**
     * البحث عن قالب مناسب لموديل
     */
    public static function findForModel(Model $model, string $event = 'created'): ?self
    {
        return static::active()
            ->forModel(get_class($model))
            ->where('trigger_event', $event)
            ->orderBy('priority', 'desc')
            ->first();
    }
}
