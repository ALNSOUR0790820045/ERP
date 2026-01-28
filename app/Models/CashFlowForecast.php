<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashFlowForecast extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'forecast_date', 'period_start', 'period_end',
        'period_type', 'opening_balance', 'inflows', 'outflows',
        'net_cash_flow', 'closing_balance', 'inflow_details', 'outflow_details',
        'assumptions', 'currency_id', 'status', 'created_by', 'notes',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'opening_balance' => 'decimal:3',
        'inflows' => 'decimal:3',
        'outflows' => 'decimal:3',
        'net_cash_flow' => 'decimal:3',
        'closing_balance' => 'decimal:3',
        'inflow_details' => 'array',
        'outflow_details' => 'array',
        'assumptions' => 'array',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
