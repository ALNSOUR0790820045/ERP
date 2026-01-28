<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id', 'warehouse_id', 'batch_number', 'production_date',
        'expiry_date', 'supplier_batch', 'supplier_id', 'purchase_order_id',
        'received_quantity', 'current_quantity', 'unit_cost',
        'status', 'notes',
    ];

    protected $casts = [
        'production_date' => 'date',
        'expiry_date' => 'date',
        'received_quantity' => 'decimal:4',
        'current_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
    ];

    public function material(): BelongsTo { return $this->belongsTo(Material::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }

    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeWithStock($query) { return $query->where('current_quantity', '>', 0); }
}
