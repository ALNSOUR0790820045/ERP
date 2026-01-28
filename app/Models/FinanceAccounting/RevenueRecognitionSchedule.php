<?php

namespace App\Models\FinanceAccounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueRecognitionSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'performance_obligation_id',
        'fiscal_period_id',
        'recognition_date',
        'amount',
        'cumulative_recognized',
        'recognition_basis',
        'notes',
        'journal_voucher_id',
        'status',
        'created_by',
    ];

    protected $casts = [
        'recognition_date' => 'date',
        'amount' => 'decimal:2',
        'cumulative_recognized' => 'decimal:2',
    ];

    public function performanceObligation(): BelongsTo
    {
        return $this->belongsTo(PerformanceObligation::class);
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function journalVoucher(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
