<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number', 'customer_id', 'invoice_id', 'project_id',
        'receipt_date', 'amount', 'payment_method', 'bank_account_id',
        'check_number', 'check_date', 'reference', 'notes',
        'status', 'received_by', 'approved_by', 'created_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'check_date' => 'date',
        'amount' => 'decimal:3',
    ];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function bankAccount(): BelongsTo { return $this->belongsTo(BankAccount::class); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
}
