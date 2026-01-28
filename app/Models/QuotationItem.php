<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id',
        'item_number',
        'description',
        'unit',
        'quantity',
        'unit_price',
        'discount_percent',
        'total_price',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount_percent' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    protected static function booted(): void
    {
        static::saving(function ($item) {
            $subtotal = $item->quantity * $item->unit_price;
            $discount = $subtotal * ($item->discount_percent / 100);
            $item->total_price = $subtotal - $discount;
        });

        static::saved(function ($item) {
            $item->quotation?->calculateTotals();
        });

        static::deleted(function ($item) {
            $item->quotation?->calculateTotals();
        });
    }
}
