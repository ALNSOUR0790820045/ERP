<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractPaymentSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'payment_number', 'description', 'payment_type',
        'due_date', 'percentage', 'amount', 'currency_id',
        'milestone_id', 'conditions', 'paid_amount', 'payment_date',
        'status', 'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_date' => 'date',
        'percentage' => 'decimal:2',
        'amount' => 'decimal:3',
        'paid_amount' => 'decimal:3',
        'conditions' => 'array',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function milestone(): BelongsTo { return $this->belongsTo(ContractMilestone::class, 'milestone_id'); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopePaid($query) { return $query->where('status', 'paid'); }
    public function scopeOverdue($query) { return $query->where('status', 'pending')->where('due_date', '<', now()); }

    public function getRemainingAmountAttribute(): float
    {
        return $this->amount - $this->paid_amount;
    }
}
