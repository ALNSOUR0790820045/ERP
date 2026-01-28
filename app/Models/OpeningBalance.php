<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpeningBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id', 'company_id', 'fiscal_year', 'debit_balance',
        'credit_balance', 'currency_id', 'exchange_rate',
        'notes', 'is_approved', 'approved_by', 'created_by',
    ];

    protected $casts = [
        'debit_balance' => 'decimal:3',
        'credit_balance' => 'decimal:3',
        'exchange_rate' => 'decimal:6',
        'is_approved' => 'boolean',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeApproved($query) { return $query->where('is_approved', true); }
    public function scopeByYear($query, int $year) { return $query->where('fiscal_year', $year); }
}
