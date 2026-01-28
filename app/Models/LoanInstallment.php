<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id', 'installment_number', 'due_date', 'amount',
        'principal_portion', 'interest_portion', 'paid_amount',
        'payment_date', 'payroll_id', 'status', 'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_date' => 'date',
        'amount' => 'decimal:3',
        'principal_portion' => 'decimal:3',
        'interest_portion' => 'decimal:3',
        'paid_amount' => 'decimal:3',
    ];

    public function loan(): BelongsTo { return $this->belongsTo(Loan::class); }
    public function payroll(): BelongsTo { return $this->belongsTo(Payroll::class); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopePaid($query) { return $query->where('status', 'paid'); }
    public function scopeOverdue($query) { return $query->where('status', 'pending')->where('due_date', '<', now()); }
}
