<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierQuoteItem extends Model
{
    protected $fillable = [
        'supplier_quote_id',
        'rfq_item_id',
        'unit_price',
        'quantity',
        'total_price',
        'discount_percentage',
        'discount_amount',
        'net_price',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:3',
        'quantity' => 'decimal:3',
        'total_price' => 'decimal:3',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:3',
        'net_price' => 'decimal:3',
    ];

    protected static function booted(): void
    {
        static::saving(function ($model) {
            $model->total_price = $model->quantity * $model->unit_price;
            
            if ($model->discount_percentage) {
                $model->discount_amount = $model->total_price * ($model->discount_percentage / 100);
            }
            
            $model->net_price = $model->total_price - ($model->discount_amount ?? 0);
        });
    }

    public function supplierQuote(): BelongsTo
    {
        return $this->belongsTo(SupplierQuote::class);
    }

    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(RfqItem::class);
    }
}
