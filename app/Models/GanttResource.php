<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * مورد Gantt
 * Gantt Resource
 */
class GanttResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'type',
        'user_id',
        'cost_per_hour',
        'cost_per_unit',
        'available_hours_per_day',
        'calendar',
        'is_active',
    ];

    protected $casts = [
        'cost_per_hour' => 'decimal:3',
        'cost_per_unit' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    // أنواع الموارد
    const TYPES = [
        'human' => 'بشري',
        'equipment' => 'معدات',
        'material' => 'مواد',
    ];

    // العلاقات
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taskResources(): HasMany
    {
        return $this->hasMany(GanttTaskResource::class, 'resource_id');
    }

    public function tasks(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(GanttTask::class, 'gantt_task_resources', 'resource_id', 'task_id')
            ->withPivot(['units', 'planned_hours', 'actual_hours', 'cost']);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHuman($query)
    {
        return $query->where('type', 'human');
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * حساب التحميل الزائد
     */
    public function getOverallocation(\Carbon\Carbon $date): float
    {
        $totalHours = $this->taskResources()
            ->whereHas('task', fn($q) => 
                $q->where('planned_start', '<=', $date)
                  ->where('planned_end', '>=', $date)
            )
            ->sum('planned_hours');

        return max(0, $totalHours - $this->available_hours_per_day);
    }
}
