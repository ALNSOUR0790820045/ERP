<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatIfScenarioChange extends Model
{
    use HasFactory;

    protected $table = 'what_if_scenario_changes';

    protected $fillable = [
        'what_if_scenario_id',
        'gantt_task_id',
        'project_resource_id',
        'change_type',
        'field_name',
        'original_value',
        'new_value',
        'reason',
        'impact_days',
        'impact_cost',
    ];

    protected $casts = [
        'impact_days' => 'decimal:2',
        'impact_cost' => 'decimal:2',
    ];

    // Relationships
    public function scenario(): BelongsTo
    {
        return $this->belongsTo(WhatIfScenario::class, 'what_if_scenario_id');
    }

    public function ganttTask(): BelongsTo
    {
        return $this->belongsTo(GanttTask::class);
    }

    public function projectResource(): BelongsTo
    {
        return $this->belongsTo(ProjectResource::class);
    }

    // Scopes
    public function scopeDurationChanges($query)
    {
        return $query->where('change_type', 'duration');
    }

    public function scopeDateChanges($query)
    {
        return $query->whereIn('change_type', ['start_date', 'end_date']);
    }

    public function scopeCostChanges($query)
    {
        return $query->where('change_type', 'cost');
    }

    public function scopeResourceChanges($query)
    {
        return $query->where('change_type', 'resource');
    }

    public function scopeAdditions($query)
    {
        return $query->where('change_type', 'add_task');
    }

    public function scopeRemovals($query)
    {
        return $query->where('change_type', 'remove_task');
    }

    // Methods
    public function getChangeDescriptionAttribute(): string
    {
        $descriptions = [
            'duration' => 'تغيير المدة',
            'start_date' => 'تغيير تاريخ البداية',
            'end_date' => 'تغيير تاريخ النهاية',
            'cost' => 'تغيير التكلفة',
            'resource' => 'تغيير المورد',
            'dependency' => 'تغيير الاعتمادية',
            'add_task' => 'إضافة نشاط',
            'remove_task' => 'حذف نشاط',
        ];

        return $descriptions[$this->change_type] ?? $this->change_type;
    }

    public function hasPositiveImpact(): bool
    {
        return ($this->impact_days ?? 0) < 0 || ($this->impact_cost ?? 0) < 0;
    }

    public function hasNegativeImpact(): bool
    {
        return ($this->impact_days ?? 0) > 0 || ($this->impact_cost ?? 0) > 0;
    }
}
