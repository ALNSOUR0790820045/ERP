<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierQuote extends Model
{
    protected $fillable = [
        'rfq_id',
        'supplier_id',
        'quote_number',
        'quote_date',
        'validity_date',
        'total_amount',
        'payment_terms',
        'delivery_terms',
        'delivery_days',
        'notes',
        'is_selected',
    ];

    protected $casts = [
        'quote_date' => 'date',
        'validity_date' => 'date',
        'total_amount' => 'decimal:3',
        'delivery_days' => 'integer',
        'is_selected' => 'boolean',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierQuoteItem::class);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->validity_date && $this->validity_date < now();
    }

    public function getCalculatedTotalAttribute(): float
    {
        return $this->items->sum('net_price');
    }
}
