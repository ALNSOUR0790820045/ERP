<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'supplier_id', 'purchase_order_id', 'project_id',
        'invoice_date', 'due_date', 'payment_terms', 'subtotal', 'discount',
        'vat_amount', 'withholding_tax', 'total_amount', 'paid_amount',
        'currency_id', 'exchange_rate', 'status', 'notes',
        'received_by', 'approved_by', 'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:3',
        'discount' => 'decimal:3',
        'vat_amount' => 'decimal:3',
        'withholding_tax' => 'decimal:3',
        'total_amount' => 'decimal:3',
        'paid_amount' => 'decimal:3',
        'exchange_rate' => 'decimal:6',
    ];

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function payments(): HasMany { return $this->hasMany(SupplierPayment::class); }

    public function getRemainingAmountAttribute(): float
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopeOverdue($query) { return $query->where('due_date', '<', now())->whereColumn('paid_amount', '<', 'total_amount'); }
}
