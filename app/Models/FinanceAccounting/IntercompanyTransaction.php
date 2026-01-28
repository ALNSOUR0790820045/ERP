<?php

namespace App\Models\FinanceAccounting;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntercompanyTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'consolidation_run_id',
        'from_company_id',
        'to_company_id',
        'transaction_type',
        'reference_number',
        'transaction_date',
        'amount',
        'currency_code',
        'exchange_rate',
        'amount_reporting_currency',
        'is_eliminated',
        'elimination_journal_id',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'amount_reporting_currency' => 'decimal:2',
        'is_eliminated' => 'boolean',
    ];

    public function consolidationRun(): BelongsTo
    {
        return $this->belongsTo(ConsolidationRun::class);
    }

    public function fromCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'from_company_id');
    }

    public function toCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'to_company_id');
    }

    public function eliminationJournal(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class, 'elimination_journal_id');
    }
}
