<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number', 'from_bank_account_id', 'to_bank_account_id',
        'transfer_date', 'amount', 'from_currency_id', 'to_currency_id',
        'exchange_rate', 'fees', 'to_amount', 'reference', 'notes',
        'status', 'approved_by', 'created_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'amount' => 'decimal:3',
        'exchange_rate' => 'decimal:6',
        'fees' => 'decimal:3',
        'to_amount' => 'decimal:3',
    ];

    public function fromBankAccount(): BelongsTo { return $this->belongsTo(BankAccount::class, 'from_bank_account_id'); }
    public function toBankAccount(): BelongsTo { return $this->belongsTo(BankAccount::class, 'to_bank_account_id'); }
    public function fromCurrency(): BelongsTo { return $this->belongsTo(Currency::class, 'from_currency_id'); }
    public function toCurrency(): BelongsTo { return $this->belongsTo(Currency::class, 'to_currency_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
}
