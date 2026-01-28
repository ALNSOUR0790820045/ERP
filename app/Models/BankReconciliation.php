<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
        'reconciliation_number',
        'statement_date',
        'statement_balance',
        'book_balance',
        'adjusted_book_balance',
        'difference',
        'deposits_in_transit',
        'outstanding_checks',
        'bank_charges',
        'bank_interest',
        'other_adjustments',
        'fiscal_period_id',
        'status',
        'prepared_by',
        'approved_by',
        'approval_date',
        'notes',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'statement_balance' => 'decimal:3',
        'book_balance' => 'decimal:3',
        'adjusted_book_balance' => 'decimal:3',
        'difference' => 'decimal:3',
        'deposits_in_transit' => 'decimal:3',
        'outstanding_checks' => 'decimal:3',
        'bank_charges' => 'decimal:3',
        'bank_interest' => 'decimal:3',
        'other_adjustments' => 'decimal:3',
        'approval_date' => 'date',
    ];

    // العلاقات
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BankReconciliationItem::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // الثوابت
    public const STATUSES = [
        'draft' => 'مسودة',
        'in_progress' => 'قيد المطابقة',
        'completed' => 'مكتمل',
        'approved' => 'معتمد',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * حساب المطابقة
     */
    public function calculate(): void
    {
        // الإيداعات في الطريق
        $this->deposits_in_transit = $this->items()
            ->where('item_type', 'deposit_in_transit')
            ->sum('amount');

        // الشيكات المعلقة
        $this->outstanding_checks = $this->items()
            ->where('item_type', 'outstanding_check')
            ->sum('amount');

        // الرسوم البنكية
        $this->bank_charges = $this->items()
            ->where('item_type', 'bank_charge')
            ->sum('amount');

        // الفوائد البنكية
        $this->bank_interest = $this->items()
            ->where('item_type', 'bank_interest')
            ->sum('amount');

        // الرصيد المعدل للدفاتر
        $this->adjusted_book_balance = $this->book_balance 
            - $this->bank_charges 
            + $this->bank_interest 
            + $this->other_adjustments;

        // رصيد كشف البنك المعدل
        $adjustedStatementBalance = $this->statement_balance 
            + $this->deposits_in_transit 
            - $this->outstanding_checks;

        // الفرق
        $this->difference = $this->adjusted_book_balance - $adjustedStatementBalance;
    }
}
