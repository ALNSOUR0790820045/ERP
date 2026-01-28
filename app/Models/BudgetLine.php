<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetLine extends Model
{
    protected $fillable = [
        'budget_id', 'account_id', 'cost_center_id', 'line_description',
        'annual_amount', 'jan_amount', 'feb_amount', 'mar_amount',
        'apr_amount', 'may_amount', 'jun_amount', 'jul_amount',
        'aug_amount', 'sep_amount', 'oct_amount', 'nov_amount', 'dec_amount',
    ];

    protected $casts = [
        'annual_amount' => 'decimal:3',
        'jan_amount' => 'decimal:3',
        'feb_amount' => 'decimal:3',
        'mar_amount' => 'decimal:3',
        'apr_amount' => 'decimal:3',
        'may_amount' => 'decimal:3',
        'jun_amount' => 'decimal:3',
        'jul_amount' => 'decimal:3',
        'aug_amount' => 'decimal:3',
        'sep_amount' => 'decimal:3',
        'oct_amount' => 'decimal:3',
        'nov_amount' => 'decimal:3',
        'dec_amount' => 'decimal:3',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
