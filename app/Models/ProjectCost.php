<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'cost_code', 'cost_category', 'description',
        'budget_amount', 'committed_amount', 'actual_amount',
        'forecast_amount', 'variance', 'currency_id',
        'unit', 'quantity', 'unit_rate', 'notes',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:3',
        'committed_amount' => 'decimal:3',
        'actual_amount' => 'decimal:3',
        'forecast_amount' => 'decimal:3',
        'variance' => 'decimal:3',
        'quantity' => 'decimal:4',
        'unit_rate' => 'decimal:4',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function actuals(): HasMany { return $this->hasMany(ProjectCostActual::class); }
    public function forecasts(): HasMany { return $this->hasMany(ProjectCostForecast::class); }

    public function getVariancePercentageAttribute(): float
    {
        return $this->budget_amount > 0 ? ($this->variance / $this->budget_amount) * 100 : 0;
    }
}
