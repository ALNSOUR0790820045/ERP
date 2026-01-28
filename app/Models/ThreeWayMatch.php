<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * المطابقة الثلاثية للمشتريات
 * 3-Way Matching: PO + GRN + Invoice
 */
class ThreeWayMatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'match_number',
        'purchase_order_id',
        'goods_receipt_id',
        'supplier_invoice_id',
        'match_status',
        'po_total',
        'grn_total',
        'invoice_total',
        'quantity_variance',
        'price_variance',
        'variance_percentage',
        'tolerance_percentage',
        'within_tolerance',
        'auto_approved',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejection_reason',
    ];

    protected $casts = [
        'po_total' => 'decimal:2',
        'grn_total' => 'decimal:2',
        'invoice_total' => 'decimal:2',
        'quantity_variance' => 'decimal:3',
        'price_variance' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'tolerance_percentage' => 'decimal:2',
        'within_tolerance' => 'boolean',
        'auto_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->match_number)) {
                $model->match_number = 'TWM-' . date('Y') . '-' . str_pad(static::count() + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function supplierInvoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ThreeWayMatchItem::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('match_status', 'pending');
    }

    public function scopeWithVariance($query)
    {
        return $query->where('match_status', 'variance');
    }

    public function scopeMatched($query)
    {
        return $query->where('match_status', 'matched');
    }

    // Methods
    public function calculateVariances(): void
    {
        $this->quantity_variance = $this->items->sum('quantity_variance');
        $this->price_variance = $this->items->sum('price_variance');
        
        if ($this->po_total > 0) {
            $this->variance_percentage = abs($this->invoice_total - $this->po_total) / $this->po_total * 100;
        }
        
        $this->within_tolerance = $this->variance_percentage <= $this->tolerance_percentage;
        $this->match_status = $this->within_tolerance ? 'matched' : 'variance';
        
        $this->save();
    }

    public function approve(int $userId, ?string $notes = null): bool
    {
        $this->approved_by = $userId;
        $this->approved_at = now();
        $this->approval_notes = $notes;
        $this->match_status = 'approved';
        return $this->save();
    }

    public function reject(int $userId, string $reason): bool
    {
        $this->approved_by = $userId;
        $this->approved_at = now();
        $this->rejection_reason = $reason;
        $this->match_status = 'rejected';
        return $this->save();
    }
}
