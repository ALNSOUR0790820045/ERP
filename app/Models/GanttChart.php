<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GanttChart extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'gantt_tasks';

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
        'duration_days' => 'integer',
        'progress' => 'decimal:2',
        'weight' => 'decimal:2',
        'is_critical' => 'boolean',
        'sort_order' => 'integer',
        'level' => 'integer',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'metadata' => 'array',
    ];

    // العلاقات
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function parent()
    {
        return $this->belongsTo(GanttChart::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(GanttChart::class, 'parent_id')->orderBy('sort_order');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function dependencies()
    {
        return $this->hasMany(GanttDependency::class, 'task_id');
    }

    public function successors()
    {
        return $this->hasMany(GanttDependency::class, 'predecessor_id');
    }

    public function resources()
    {
        return $this->belongsToMany(GanttResource::class, 'gantt_task_resources', 'task_id', 'resource_id')
            ->withPivot(['allocation_percentage', 'start_date', 'end_date', 'hours_assigned']);
    }

    public function baselines()
    {
        return $this->hasMany(GanttBaseline::class, 'task_id');
    }

    // Scopes
    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    public function scopeNotStarted($query)
    {
        return $query->where('status', 'not_started');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeMilestones($query)
    {
        return $query->where('task_type', 'milestone');
    }

    public function scopeTasks($query)
    {
        return $query->where('task_type', 'task');
    }

    public function scopeRootTasks($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOverdue($query)
    {
        return $query->where('planned_end', '<', now())
            ->where('status', '!=', 'completed');
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'not_started' => 'لم يبدأ',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'on_hold' => 'متوقف',
            'cancelled' => 'ملغي',
            default => $this->status,
        };
    }

    public function getPriorityLabelAttribute()
    {
        return match($this->priority) {
            'low' => 'منخفض',
            'medium' => 'متوسط',
            'high' => 'عالي',
            'critical' => 'حرج',
            default => $this->priority,
        };
    }

    public function getTaskTypeLabelAttribute()
    {
        return match($this->task_type) {
            'task' => 'مهمة',
            'milestone' => 'معلم',
            'summary' => 'ملخص',
            'project' => 'مشروع',
            default => $this->task_type,
        };
    }

    public function getVarianceDaysAttribute()
    {
        if (!$this->actual_end || !$this->planned_end) {
            return null;
        }
        return $this->actual_end->diffInDays($this->planned_end, false);
    }

    public function getIsOverdueAttribute()
    {
        return $this->planned_end < now() && $this->status !== 'completed';
    }

    public function getCostVarianceAttribute()
    {
        if (!$this->estimated_cost || !$this->actual_cost) {
            return null;
        }
        return $this->estimated_cost - $this->actual_cost;
    }

    // Methods
    public function updateProgress($progress)
    {
        $this->update(['progress' => min(100, max(0, $progress))]);
        
        if ($progress >= 100) {
            $this->update([
                'status' => 'completed',
                'actual_end' => $this->actual_end ?? now(),
            ]);
        } elseif ($progress > 0) {
            $this->update([
                'status' => 'in_progress',
                'actual_start' => $this->actual_start ?? now(),
            ]);
        }
        
        // Update parent progress
        if ($this->parent) {
            $this->parent->recalculateProgress();
        }
    }

    public function recalculateProgress()
    {
        $children = $this->children;
        if ($children->isEmpty()) {
            return;
        }

        $totalWeight = $children->sum('weight') ?: $children->count();
        $weightedProgress = $children->sum(function ($child) use ($totalWeight) {
            $weight = $child->weight ?: 1;
            return ($weight / $totalWeight) * $child->progress;
        });

        $this->update(['progress' => round($weightedProgress, 2)]);
    }

    public function getFullPath()
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }

    // Static Methods
    public static function getProjectGantt($projectId)
    {
        return static::where('project_id', $projectId)
            ->with(['children', 'dependencies', 'assignedUser'])
            ->orderBy('sort_order')
            ->get()
            ->toTree();
    }

    public function toTree()
    {
        return $this->where('parent_id', null)
            ->with('children')
            ->orderBy('sort_order')
            ->get();
    }
}
