<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderItem extends Model
{
    protected $fillable = [
        'production_order_id',
        'material_id',
        'item_name',
        'planned_quantity',
        'actual_quantity',
        'unit',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:4',
        'actual_quantity' => 'decimal:4',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function getVarianceAttribute(): float
    {
        return $this->actual_quantity - $this->planned_quantity;
    }
}
