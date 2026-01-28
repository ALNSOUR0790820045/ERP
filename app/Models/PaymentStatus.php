<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'payable_type', 'payable_id', 'status', 'status_date',
        'expected_payment_date', 'actual_payment_date', 'amount_due',
        'amount_paid', 'balance', 'payment_reference', 'bank_reference',
        'delay_days', 'follow_up_notes', 'updated_by',
    ];

    protected $casts = [
        'status_date' => 'date',
        'expected_payment_date' => 'date',
        'actual_payment_date' => 'date',
        'amount_due' => 'decimal:3',
        'amount_paid' => 'decimal:3',
        'balance' => 'decimal:3',
    ];

    public function payable()
    {
        return $this->morphTo();
    }

    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopePaid($query) { return $query->where('status', 'paid'); }
    public function scopeOverdue($query) { 
        return $query->where('status', 'pending')
            ->where('expected_payment_date', '<', now()); 
    }
}
