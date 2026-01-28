<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatifResult extends Model
{
    protected $fillable = [
        'whatif_scenario_id',
        'result_type',
        'metric_name',
        'metric_name_ar',
        'baseline_value',
        'scenario_value',
        'variance',
        'variance_percentage',
        'impact_level',
        'breakdown',
    ];

    protected $casts = [
        'baseline_value' => 'decimal:2',
        'scenario_value' => 'decimal:2',
        'variance' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'breakdown' => 'array',
    ];

    public static array $impactLevelColors = [
        'positive' => 'success',
        'neutral' => 'gray',
        'minor' => 'info',
        'moderate' => 'warning',
        'severe' => 'danger',
    ];

    public static array $impactLevelLabels = [
        'positive' => 'إيجابي',
        'neutral' => 'محايد',
        'minor' => 'طفيف',
        'moderate' => 'متوسط',
        'severe' => 'حاد',
    ];

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(WhatifScenario::class, 'whatif_scenario_id');
    }
}
