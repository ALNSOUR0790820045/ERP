<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YearEndClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'fiscal_year_id',
        'closing_type',
        'closing_date',
        'status',
        
        // أرصدة الإيرادات والمصروفات
        'total_revenues',
        'total_expenses',
        'net_income',
        
        // الترحيل للأرباح المحتجزة
        'retained_earnings_account_id',
        'retained_earnings_entry_id',
        
        // الأرصدة الافتتاحية
        'opening_balances_created',
        'opening_balances_date',
        
        'closed_by',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'closing_date' => 'date',
        'total_revenues' => 'decimal:3',
        'total_expenses' => 'decimal:3',
        'net_income' => 'decimal:3',
        'opening_balances_created' => 'boolean',
        'opening_balances_date' => 'date',
        'closed_at' => 'datetime',
    ];

    // العلاقات
    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function retainedEarningsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'retained_earnings_account_id');
    }

    public function retainedEarningsEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'retained_earnings_entry_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    // الثوابت
    public const CLOSING_TYPES = [
        'interim' => 'إقفال مؤقت',
        'final' => 'إقفال نهائي',
    ];

    public const STATUSES = [
        'pending' => 'معلق',
        'in_progress' => 'قيد التنفيذ',
        'completed' => 'مكتمل',
        'rolled_back' => 'ملغي',
    ];

    /**
     * حساب صافي الدخل
     */
    public function calculateNetIncome(): void
    {
        // حساب إجمالي الإيرادات (حسابات الفئة 4)
        $this->total_revenues = Account::where('account_type', 'revenue')
            ->whereHas('journalEntryDetails', function ($q) {
                $q->whereHas('journalEntry', function ($je) {
                    $je->where('fiscal_year_id', $this->fiscal_year_id);
                });
            })
            ->sum('credit') - Account::where('account_type', 'revenue')
            ->sum('debit');

        // حساب إجمالي المصروفات (حسابات الفئة 5)
        $this->total_expenses = Account::where('account_type', 'expense')
            ->whereHas('journalEntryDetails', function ($q) {
                $q->whereHas('journalEntry', function ($je) {
                    $je->where('fiscal_year_id', $this->fiscal_year_id);
                });
            })
            ->sum('debit') - Account::where('account_type', 'expense')
            ->sum('credit');

        $this->net_income = $this->total_revenues - $this->total_expenses;
    }
}
