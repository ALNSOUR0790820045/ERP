<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    protected $fillable = [
        'receipt_id', 'material_id', 'po_item_id', 'ordered_qty',
        'received_qty', 'accepted_qty', 'rejected_qty', 'unit_cost',
        'rejection_reason', 'location',
    ];

    protected $casts = [
        'ordered_qty' => 'decimal:4',
        'received_qty' => 'decimal:4',
        'accepted_qty' => 'decimal:4',
        'rejected_qty' => 'decimal:4',
        'unit_cost' => 'decimal:3',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'receipt_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'po_item_id');
    }
}
