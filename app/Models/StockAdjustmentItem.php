<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_adjustment_id',
        'material_id',
        'batch_number',
        'current_quantity',
        'adjusted_quantity',
        'adjustment_quantity',
        'unit_cost',
        'adjustment_value',
        'reason',
    ];

    protected $casts = [
        'current_quantity' => 'decimal:3',
        'adjusted_quantity' => 'decimal:3',
        'adjustment_quantity' => 'decimal:3',
        'unit_cost' => 'decimal:3',
        'adjustment_value' => 'decimal:3',
    ];

    // العلاقات
    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->adjustment_quantity = $item->adjusted_quantity - $item->current_quantity;
            $item->adjustment_value = $item->adjustment_quantity * $item->unit_cost;
        });
    }
}
