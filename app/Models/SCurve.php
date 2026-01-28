<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SCurve extends Model
{
    use HasFactory;

    protected $table = 's_curves';

    protected $fillable = [
        'project_id', 'curve_type', 'curve_name', 'data_date',
        'baseline_data', 'planned_data', 'actual_data', 'forecast_data',
        'earned_data', 'periods', 'period_type', 'currency_id',
        'total_baseline', 'total_planned', 'total_actual', 'total_forecast',
        'status', 'notes',
    ];

    protected $casts = [
        'data_date' => 'date',
        'baseline_data' => 'array',
        'planned_data' => 'array',
        'actual_data' => 'array',
        'forecast_data' => 'array',
        'earned_data' => 'array',
        'periods' => 'array',
        'total_baseline' => 'decimal:3',
        'total_planned' => 'decimal:3',
        'total_actual' => 'decimal:3',
        'total_forecast' => 'decimal:3',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
}
