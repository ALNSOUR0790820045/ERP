<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialStatement extends Model
{
    use HasFactory;

    protected $fillable = [
        'statement_type', 'title', 'fiscal_year', 'period_from', 'period_to',
        'company_id', 'data', 'status', 'generated_at', 'generated_by',
        'approved_at', 'approved_by', 'notes',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'generated_at' => 'datetime',
        'approved_at' => 'datetime',
        'data' => 'array',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function generator(): BelongsTo { return $this->belongsTo(User::class, 'generated_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeBalanceSheet($query) { return $query->where('statement_type', 'balance_sheet'); }
    public function scopeIncomeStatement($query) { return $query->where('statement_type', 'income_statement'); }
    public function scopeCashFlow($query) { return $query->where('statement_type', 'cash_flow'); }
}
