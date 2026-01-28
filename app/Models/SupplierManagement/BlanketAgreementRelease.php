<?php

namespace App\Models\SupplierManagement;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlanketAgreementRelease extends Model
{
    protected $fillable = [
        'blanket_agreement_id', 'blanket_item_id', 'release_number', 'purchase_order_id',
        'release_date', 'released_by', 'released_quantity', 'released_amount', 'unit_price',
        'currency', 'delivery_date', 'delivery_location', 'status', 'notes',
        'approved_by', 'approved_at', 'received_quantity', 'received_date', 'metadata',
    ];

    protected $casts = [
        'release_date' => 'date', 'delivery_date' => 'date', 'received_date' => 'date',
        'approved_at' => 'datetime', 'released_quantity' => 'decimal:3',
        'released_amount' => 'decimal:2', 'unit_price' => 'decimal:4',
        'received_quantity' => 'decimal:3', 'metadata' => 'array',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_ORDERED = 'ordered';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    public function blanketAgreement(): BelongsTo { return $this->belongsTo(BlanketPurchaseAgreement::class, 'blanket_agreement_id'); }
    public function item(): BelongsTo { return $this->belongsTo(BlanketAgreementItem::class, 'blanket_item_id'); }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function releasedBy(): BelongsTo { return $this->belongsTo(User::class, 'released_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function approve(User $approver): void {
        $this->update(['status' => self::STATUS_APPROVED, 'approved_by' => $approver->id, 'approved_at' => now()]);
    }

    public function createPurchaseOrder(): ?PurchaseOrder {
        // Logic to create PO from release
        $po = PurchaseOrder::create([
            'supplier_id' => $this->blanketAgreement->supplier_id,
            'blanket_agreement_id' => $this->blanket_agreement_id,
            'order_date' => now(),
            'delivery_date' => $this->delivery_date,
            'total_amount' => $this->released_amount,
        ]);
        $this->update(['purchase_order_id' => $po->id, 'status' => self::STATUS_ORDERED]);
        return $po;
    }

    public function receive(float $quantity): void {
        $this->update([
            'received_quantity' => $quantity,
            'received_date' => now(),
            'status' => self::STATUS_RECEIVED,
        ]);
        $this->blanketAgreement->updateReleased();
    }

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->release_number) {
                $prefix = 'REL-' . ($model->blanket_agreement_id ?? '0') . '-';
                $model->release_number = $prefix . str_pad(
                    BlanketAgreementRelease::where('blanket_agreement_id', $model->blanket_agreement_id)->count() + 1,
                    4, '0', STR_PAD_LEFT
                );
            }
        });
    }
}
