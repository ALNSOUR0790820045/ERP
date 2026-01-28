<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Check extends Model
{
    use HasFactory;

    protected $fillable = [
        'check_number', 'bank_account_id', 'check_type', 'payee_type',
        'payee_id', 'payee_name', 'amount', 'currency_id', 'issue_date',
        'due_date', 'deposit_date', 'clear_date', 'reference',
        'status', 'bounce_reason', 'notes', 'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'deposit_date' => 'date',
        'clear_date' => 'date',
        'amount' => 'decimal:3',
    ];

    public function bankAccount(): BelongsTo { return $this->belongsTo(BankAccount::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function payee()
    {
        if ($this->payee_type === 'supplier') {
            return $this->belongsTo(Supplier::class, 'payee_id');
        } elseif ($this->payee_type === 'customer') {
            return $this->belongsTo(Customer::class, 'payee_id');
        }
        return null;
    }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeCleared($query) { return $query->where('status', 'cleared'); }
    public function scopeBounced($query) { return $query->where('status', 'bounced'); }
    public function scopeOutgoing($query) { return $query->where('check_type', 'outgoing'); }
    public function scopeIncoming($query) { return $query->where('check_type', 'incoming'); }
}
