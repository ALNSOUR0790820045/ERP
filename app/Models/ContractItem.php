<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'parent_id',
        'item_number',
        'description',
        'description_en',
        'unit_id',
        'unit_code',
        'item_type',
        'contract_qty',
        'unit_rate',
        'total_amount',
        'executed_qty',
        'executed_amount',
        'variation_qty',
        'variation_amount',
        'sort_order',
        'is_header',
    ];

    protected $casts = [
        'contract_qty' => 'decimal:6',
        'unit_rate' => 'decimal:3',
        'total_amount' => 'decimal:3',
        'executed_qty' => 'decimal:6',
        'executed_amount' => 'decimal:3',
        'variation_qty' => 'decimal:6',
        'variation_amount' => 'decimal:3',
        'is_header' => 'boolean',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'parent_id')->orderBy('sort_order');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function variationItems(): HasMany
    {
        return $this->hasMany(ContractVariationItem::class);
    }

    // Accessors
    public function getCurrentQtyAttribute(): float
    {
        return $this->contract_qty + $this->variation_qty;
    }

    public function getCurrentAmountAttribute(): float
    {
        return $this->total_amount + $this->variation_amount;
    }

    public function getRemainingQtyAttribute(): float
    {
        return $this->current_qty - $this->executed_qty;
    }

    public function getExecutionPercentageAttribute(): float
    {
        if ($this->current_qty <= 0) {
            return 0;
        }
        return round(($this->executed_qty / $this->current_qty) * 100, 2);
    }
}
