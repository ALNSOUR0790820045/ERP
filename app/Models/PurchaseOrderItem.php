<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'request_item_id', 'item_code', 'description',
        'quantity', 'unit_id', 'unit_price', 'discount_percentage', 'total_price',
        'received_qty', 'remaining_qty',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:3',
        'total_price' => 'decimal:3',
        'received_qty' => 'decimal:4',
        'remaining_qty' => 'decimal:4',
    ];

    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function unit(): BelongsTo { return $this->belongsTo(Unit::class); }
}
