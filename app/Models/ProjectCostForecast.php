<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectCostForecast extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_cost_id', 'forecast_date', 'forecast_period',
        'forecast_type', 'description', 'quantity', 'unit_rate',
        'amount', 'confidence_level', 'assumptions', 'notes', 'created_by',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'quantity' => 'decimal:4',
        'unit_rate' => 'decimal:4',
        'amount' => 'decimal:3',
        'confidence_level' => 'decimal:2',
        'assumptions' => 'array',
    ];

    public function projectCost(): BelongsTo { return $this->belongsTo(ProjectCost::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
