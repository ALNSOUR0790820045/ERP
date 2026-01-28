<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePriceAdjustment extends Model
{
    protected $fillable = [
        'invoice_id',
        'calculation_date',
        'base_amount',
        'adjustment_factor',
        'adjustment_amount',
        'index_values',
        'calculation_notes',
    ];

    protected $casts = [
        'calculation_date' => 'date',
        'base_amount' => 'decimal:3',
        'adjustment_factor' => 'decimal:6',
        'adjustment_amount' => 'decimal:3',
        'index_values' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getAdjustmentPercentageAttribute(): float
    {
        return ($this->adjustment_factor - 1) * 100;
    }
}
