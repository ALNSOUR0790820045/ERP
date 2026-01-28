<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'request_id', 'supplier_id', 'project_id', 'company_id',
        'po_number', 'po_date', 'delivery_date', 'delivery_location',
        'subtotal', 'discount_percentage', 'discount_amount',
        'tax_percentage', 'tax_amount', 'total_amount', 'currency_id',
        'payment_terms_days', 'terms_conditions', 'notes',
        'status', 'approved_by', 'approved_at', 'created_by',
    ];

    protected $casts = [
        'status' => PurchaseOrderStatus::class,
        'po_date' => 'date',
        'delivery_date' => 'date',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:3',
        'discount_amount' => 'decimal:3',
        'tax_amount' => 'decimal:3',
        'total_amount' => 'decimal:3',
    ];

    public function request(): BelongsTo { return $this->belongsTo(PurchaseRequest::class, 'request_id'); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function items(): HasMany { return $this->hasMany(PurchaseOrderItem::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function getReceivedPercentageAttribute(): float
    {
        $totalQty = $this->items->sum('quantity');
        if ($totalQty <= 0) return 0;
        return round(($this->items->sum('received_qty') / $totalQty) * 100, 2);
    }
}
