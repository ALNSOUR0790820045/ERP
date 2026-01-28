<?php

namespace App\Models\FinanceAccounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariableConsideration extends Model
{
    use HasFactory;

    protected $fillable = [
        'revenue_contract_id',
        'consideration_type',
        'name',
        'description',
        'estimation_method',
        'estimated_amount',
        'constraint_amount',
        'actual_amount',
        'resolution_date',
        'status',
    ];

    protected $casts = [
        'estimated_amount' => 'decimal:2',
        'constraint_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'resolution_date' => 'date',
    ];

    public function revenueContract(): BelongsTo
    {
        return $this->belongsTo(RevenueContract::class);
    }
}
