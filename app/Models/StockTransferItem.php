<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_transfer_id',
        'material_id',
        'batch_number',
        'requested_quantity',
        'shipped_quantity',
        'received_quantity',
        'variance_quantity',
        'unit_cost',
        'total_value',
        'variance_reason',
        'notes',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:3',
        'shipped_quantity' => 'decimal:3',
        'received_quantity' => 'decimal:3',
        'variance_quantity' => 'decimal:3',
        'unit_cost' => 'decimal:3',
        'total_value' => 'decimal:3',
    ];

    // العلاقات
    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // حساب الفرق
            $item->variance_quantity = $item->shipped_quantity - $item->received_quantity;
            $item->total_value = $item->shipped_quantity * $item->unit_cost;
        });
    }
}
