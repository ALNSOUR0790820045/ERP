<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatIfScenario extends Model
{
    use HasFactory;

    protected $table = 'what_if_scenarios';

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'scenario_type',
        'status',
        'is_baseline',
        'baseline_start_date',
        'baseline_end_date',
        'baseline_cost',
        'scenario_start_date',
        'scenario_end_date',
        'scenario_cost',
        'schedule_variance_days',
        'cost_variance',
        'schedule_variance_percent',
        'cost_variance_percent',
        'assumptions',
        'impact_summary',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'is_baseline' => 'boolean',
        'baseline_start_date' => 'date',
        'baseline_end_date' => 'date',
        'baseline_cost' => 'decimal:2',
        'scenario_start_date' => 'date',
        'scenario_end_date' => 'date',
        'scenario_cost' => 'decimal:2',
        'cost_variance' => 'decimal:2',
        'assumptions' => 'array',
        'impact_summary' => 'array',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(WhatIfScenarioChange::class);
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeBaseline($query)
    {
        return $query->where('is_baseline', true);
    }

    public function scopeScheduleType($query)
    {
        return $query->where('scenario_type', 'schedule');
    }

    public function scopeCostType($query)
    {
        return $query->where('scenario_type', 'cost');
    }

    // Methods
    public function calculateVariances(): void
    {
        if ($this->baseline_end_date && $this->scenario_end_date) {
            $this->schedule_variance_days = $this->scenario_end_date->diffInDays($this->baseline_end_date, false);
            
            if ($this->baseline_end_date->diffInDays($this->project->start_date ?? $this->baseline_start_date) > 0) {
                $baselineDuration = $this->baseline_end_date->diffInDays($this->baseline_start_date);
                $this->schedule_variance_percent = $baselineDuration > 0 
                    ? round(($this->schedule_variance_days / $baselineDuration) * 100, 2) 
                    : 0;
            }
        }

        if ($this->baseline_cost && $this->scenario_cost) {
            $this->cost_variance = $this->scenario_cost - $this->baseline_cost;
            $this->cost_variance_percent = $this->baseline_cost > 0 
                ? round(($this->cost_variance / $this->baseline_cost) * 100, 2) 
                : 0;
        }

        $this->save();
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    public function approve(int $userId): void
    {
        $this->update([
            'approved_by' => $userId,
            'approved_at' => now(),
            'status' => 'active',
        ]);
    }

    public function getImpactSummaryTextAttribute(): string
    {
        $parts = [];
        
        if ($this->schedule_variance_days) {
            $direction = $this->schedule_variance_days > 0 ? 'تأخير' : 'تقديم';
            $parts[] = sprintf('%s %d يوم', $direction, abs($this->schedule_variance_days));
        }
        
        if ($this->cost_variance) {
            $direction = $this->cost_variance > 0 ? 'زيادة' : 'توفير';
            $parts[] = sprintf('%s %s', $direction, number_format(abs($this->cost_variance), 2));
        }
        
        return implode(' | ', $parts) ?: 'لا يوجد تأثير';
    }
}
