<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * مهمة Gantt
 * Gantt Task
 */
class GanttTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'parent_id',
        'task_code',
        'name',
        'description',
        'planned_start',
        'planned_end',
        'actual_start',
        'actual_end',
        'duration_days',
        'progress',
        'weight',
        'task_type',
        'status',
        'priority',
        'is_critical',
        'sort_order',
        'level',
        'wbs_code',
        'assigned_to',
        'estimated_hours',
        'actual_hours',
        'estimated_cost',
        'actual_cost',
        'color',
        'metadata',
    ];

    protected $casts = [
        'planned_start' => 'date',
        'planned_end' => 'date',
        'actual_start' => 'date',
        'actual_end' => 'date',
        'progress' => 'decimal:2',
        'weight' => 'decimal:2',
        'is_critical' => 'boolean',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'estimated_cost' => 'decimal:3',
        'actual_cost' => 'decimal:3',
        'metadata' => 'array',
    ];

    // أنواع المهام
    const TASK_TYPES = [
        'task' => 'مهمة',
        'milestone' => 'معلم',
        'summary' => 'ملخص',
        'project' => 'مشروع فرعي',
    ];

    // الحالات
    const STATUSES = [
        'not_started' => 'لم تبدأ',
        'in_progress' => 'قيد التنفيذ',
        'completed' => 'مكتملة',
        'on_hold' => 'متوقفة',
        'cancelled' => 'ملغاة',
    ];

    // الأولويات
    const PRIORITIES = [
        'low' => 'منخفضة',
        'medium' => 'متوسطة',
        'high' => 'عالية',
        'critical' => 'حرجة',
    ];

    // العلاقات
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(GanttTask::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(GanttTask::class, 'parent_id')->orderBy('sort_order');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function predecessors(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            GanttTask::class,
            'gantt_dependencies',
            'successor_id',
            'predecessor_id'
        )->withPivot(['dependency_type', 'lag_days']);
    }

    public function successors(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            GanttTask::class,
            'gantt_dependencies',
            'predecessor_id',
            'successor_id'
        )->withPivot(['dependency_type', 'lag_days']);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(GanttTaskResource::class, 'task_id');
    }

    // Scopes
    public function scopeRootTasks($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'not_started' => 'gray',
            'in_progress' => 'info',
            'completed' => 'success',
            'on_hold' => 'warning',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low' => 'gray',
            'medium' => 'info',
            'high' => 'warning',
            'critical' => 'danger',
            default => 'gray',
        };
    }

    public function getTaskTypeLabelAttribute(): string
    {
        return self::TASK_TYPES[$this->task_type] ?? $this->task_type;
    }

    public function getDelayDaysAttribute(): int
    {
        if ($this->status === 'completed' && $this->actual_end) {
            return max(0, $this->actual_end->diffInDays($this->planned_end, false));
        }
        
        if (in_array($this->status, ['not_started', 'in_progress']) && $this->planned_end->isPast()) {
            return now()->diffInDays($this->planned_end);
        }
        
        return 0;
    }

    public function getIsDelayedAttribute(): bool
    {
        return $this->delay_days > 0;
    }

    // Methods
    /**
     * حساب مدة المهمة
     */
    public function calculateDuration(): int
    {
        return $this->planned_start->diffInDays($this->planned_end) + 1;
    }

    /**
     * تحديث التقدم من المهام الفرعية
     */
    public function updateProgressFromChildren(): void
    {
        $children = $this->children;
        if ($children->isEmpty()) return;

        $totalWeight = $children->sum('weight');
        if ($totalWeight == 0) {
            $this->progress = $children->avg('progress');
        } else {
            $weightedProgress = $children->sum(fn($c) => $c->progress * $c->weight);
            $this->progress = $weightedProgress / $totalWeight;
        }
        
        $this->save();

        // تحديث الأب أيضاً
        if ($this->parent) {
            $this->parent->updateProgressFromChildren();
        }
    }

    /**
     * الحصول على بيانات Gantt للـ JavaScript
     */
    public function toGanttData(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->name,
            'start_date' => $this->planned_start->format('Y-m-d'),
            'end_date' => $this->planned_end->format('Y-m-d'),
            'duration' => $this->duration_days,
            'progress' => $this->progress / 100,
            'parent' => $this->parent_id ?? 0,
            'type' => $this->task_type === 'milestone' ? 'milestone' : 'task',
            'open' => true,
            'color' => $this->color ?? $this->getDefaultColor(),
            'priority' => $this->priority,
            'status' => $this->status,
            'assigned_to' => $this->assigned_to,
        ];
    }

    /**
     * الحصول على اللون الافتراضي
     */
    protected function getDefaultColor(): string
    {
        if ($this->is_critical) return '#ef4444';
        
        return match($this->status) {
            'completed' => '#22c55e',
            'in_progress' => '#3b82f6',
            'on_hold' => '#f59e0b',
            default => '#6b7280',
        };
    }

    /**
     * الحصول على شجرة المهام
     */
    public static function getTree(int $projectId): \Illuminate\Support\Collection
    {
        return static::forProject($projectId)
            ->rootTasks()
            ->with('children.children')
            ->orderBy('sort_order')
            ->get();
    }
}
