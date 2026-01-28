<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_number', 'employee_id', 'loan_type', 'loan_date',
        'principal_amount', 'interest_rate', 'total_amount',
        'installment_amount', 'number_of_installments', 'paid_installments',
        'remaining_balance', 'start_date', 'end_date',
        'purpose', 'status', 'approved_by', 'approved_at', 'notes',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'principal_amount' => 'decimal:3',
        'interest_rate' => 'decimal:4',
        'total_amount' => 'decimal:3',
        'installment_amount' => 'decimal:3',
        'remaining_balance' => 'decimal:3',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function installments(): HasMany { return $this->hasMany(LoanInstallment::class); }

    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopePending($query) { return $query->where('status', 'pending'); }
}
