<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'code', 'barcode', 'name_ar', 'name_en',
        'description', 'specifications', 'unit_id', 'purchase_unit_id',
        'conversion_factor', 'min_stock', 'max_stock', 'reorder_point',
        'reorder_qty', 'last_purchase_price', 'average_cost', 'standard_cost',
        'valuation_method', 'is_serialized', 'is_active',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
        'min_stock' => 'decimal:4',
        'max_stock' => 'decimal:4',
        'reorder_point' => 'decimal:4',
        'reorder_qty' => 'decimal:4',
        'last_purchase_price' => 'decimal:3',
        'average_cost' => 'decimal:3',
        'standard_cost' => 'decimal:3',
        'is_serialized' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function purchaseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }

    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function getTotalStockAttribute(): float
    {
        return $this->inventoryBalances()->sum('quantity');
    }
}
