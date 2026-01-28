<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number', 'supplier_invoice_id', 'supplier_id', 'payment_date',
        'amount', 'payment_method', 'bank_account_id', 'check_id', 'reference',
        'notes', 'status', 'approved_by', 'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:3',
    ];

    public function supplierInvoice(): BelongsTo { return $this->belongsTo(SupplierInvoice::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function bankAccount(): BelongsTo { return $this->belongsTo(BankAccount::class); }
    public function check(): BelongsTo { return $this->belongsTo(Check::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
}
