<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoqMaterial extends Model
{
    protected $fillable = [
        'boq_item_id',
        'material_name',
        'description',
        'unit_id',
        'quantity_per_unit',
        'wastage_percentage',
        'total_quantity',
        'unit_price',
        'supplier_name',
        'quote_date',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'quantity_per_unit' => 'decimal:6',
        'wastage_percentage' => 'decimal:2',
        'total_quantity' => 'decimal:3',
        'unit_price' => 'decimal:3',
        'total_cost' => 'decimal:3',
        'quote_date' => 'date',
    ];

    public function boqItem(): BelongsTo
    {
        return $this->belongsTo(BoqItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    protected static function booted(): void
    {
        static::saving(function (BoqMaterial $material) {
            // حساب الكمية الإجمالية مع الهدر
            $boqQuantity = $material->boqItem->quantity ?? 0;
            $wastageMultiplier = 1 + ($material->wastage_percentage / 100);
            $material->total_quantity = $boqQuantity * $material->quantity_per_unit * $wastageMultiplier;
            
            // حساب التكلفة الإجمالية
            $material->total_cost = $material->total_quantity * $material->unit_price;
        });

        static::saved(function (BoqMaterial $material) {
            $material->boqItem->calculateCosts();
        });

        static::deleted(function (BoqMaterial $material) {
            $material->boqItem->calculateCosts();
        });
    }
}
