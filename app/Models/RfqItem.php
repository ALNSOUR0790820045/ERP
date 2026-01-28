<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RfqItem extends Model
{
    protected $fillable = [
        'rfq_id',
        'purchase_request_item_id',
        'material_id',
        'description',
        'specifications',
        'unit',
        'quantity',
        'estimated_price',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'estimated_price' => 'decimal:3',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function purchaseRequestItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestItem::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function quoteItems(): HasMany
    {
        return $this->hasMany(SupplierQuoteItem::class);
    }

    public function getEstimatedTotalAttribute(): float
    {
        return $this->quantity * ($this->estimated_price ?? 0);
    }

    public function getLowestQuoteAttribute()
    {
        return $this->quoteItems()->orderBy('unit_price')->first();
    }
}
