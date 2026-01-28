<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * سيناريو تحليل What-If
 * What-If Analysis Scenario
 */
class WhatifScenario extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'name',
        'name_ar',
        'description',
        'scenario_type',
        'status',
        'is_baseline',
        'original_end_date',
        'projected_end_date',
        'schedule_impact_days',
        'original_cost',
        'projected_cost',
        'cost_impact',
        'cost_impact_percentage',
        'confidence_level',
        'probability_distribution',
        'created_by',
        'analyzed_by',
        'analyzed_at',
    ];

    protected $casts = [
        'is_baseline' => 'boolean',
        'original_end_date' => 'date',
        'projected_end_date' => 'date',
        'schedule_impact_days' => 'integer',
        'original_cost' => 'decimal:2',
        'projected_cost' => 'decimal:2',
        'cost_impact' => 'decimal:2',
        'cost_impact_percentage' => 'decimal:2',
        'confidence_level' => 'decimal:2',
        'probability_distribution' => 'array',
        'analyzed_at' => 'datetime',
    ];

    public static array $scenarioTypes = [
        'schedule' => 'جدول زمني',
        'cost' => 'تكلفة',
        'resource' => 'موارد',
        'risk' => 'مخاطر',
        'combined' => 'مجمع',
    ];

    public static array $statusLabels = [
        'draft' => 'مسودة',
        'analyzing' => 'قيد التحليل',
        'completed' => 'مكتمل',
        'archived' => 'مؤرشف',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function analyzedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analyzed_by');
    }

    public function assumptions(): HasMany
    {
        return $this->hasMany(WhatifAssumption::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(WhatifResult::class);
    }

    // Methods
    public function analyze(): void
    {
        $this->status = 'analyzing';
        $this->save();

        // Calculate based on assumptions
        $scheduleImpact = 0;
        $costImpact = 0;

        foreach ($this->assumptions as $assumption) {
            switch ($assumption->assumption_type) {
                case 'activity_delay':
                    $scheduleImpact += $assumption->assumed_value * ($assumption->probability / 100);
                    break;
                case 'activity_acceleration':
                    $scheduleImpact -= $assumption->assumed_value * ($assumption->probability / 100);
                    break;
                case 'cost_increase':
                    $costImpact += $assumption->assumed_value * ($assumption->probability / 100);
                    break;
                case 'cost_decrease':
                    $costImpact -= $assumption->assumed_value * ($assumption->probability / 100);
                    break;
            }
        }

        $this->schedule_impact_days = (int)$scheduleImpact;
        $this->projected_end_date = $this->original_end_date?->addDays($scheduleImpact);
        $this->cost_impact = $costImpact;
        $this->projected_cost = $this->original_cost + $costImpact;
        
        if ($this->original_cost > 0) {
            $this->cost_impact_percentage = ($costImpact / $this->original_cost) * 100;
        }

        $this->analyzed_by = auth()->id();
        $this->analyzed_at = now();
        $this->status = 'completed';
        $this->save();

        $this->generateResults();
    }

    protected function generateResults(): void
    {
        // Schedule Result
        $this->results()->create([
            'result_type' => 'schedule',
            'metric_name' => 'Project End Date',
            'metric_name_ar' => 'تاريخ انتهاء المشروع',
            'baseline_value' => $this->original_end_date?->timestamp ?? 0,
            'scenario_value' => $this->projected_end_date?->timestamp ?? 0,
            'variance' => $this->schedule_impact_days,
            'variance_percentage' => 0,
            'impact_level' => $this->getImpactLevel($this->schedule_impact_days, 'schedule'),
        ]);

        // Cost Result
        $this->results()->create([
            'result_type' => 'cost',
            'metric_name' => 'Total Project Cost',
            'metric_name_ar' => 'إجمالي تكلفة المشروع',
            'baseline_value' => $this->original_cost,
            'scenario_value' => $this->projected_cost,
            'variance' => $this->cost_impact,
            'variance_percentage' => $this->cost_impact_percentage,
            'impact_level' => $this->getImpactLevel($this->cost_impact_percentage, 'cost'),
        ]);
    }

    protected function getImpactLevel(float $value, string $type): string
    {
        if ($value <= 0) return 'positive';
        
        $thresholds = [
            'schedule' => ['minor' => 7, 'moderate' => 30, 'severe' => 60],
            'cost' => ['minor' => 5, 'moderate' => 15, 'severe' => 25],
        ];
        
        $t = $thresholds[$type] ?? $thresholds['cost'];
        
        if (abs($value) <= $t['minor']) return 'minor';
        if (abs($value) <= $t['moderate']) return 'moderate';
        return 'severe';
    }

    public function duplicate(string $newName): self
    {
        $clone = $this->replicate();
        $clone->name = $newName;
        $clone->status = 'draft';
        $clone->analyzed_at = null;
        $clone->analyzed_by = null;
        $clone->save();

        foreach ($this->assumptions as $assumption) {
            $clone->assumptions()->create($assumption->toArray());
        }

        return $clone;
    }
}
