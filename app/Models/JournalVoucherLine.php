<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalVoucherLine extends Model
{
    protected $fillable = [
        'voucher_id', 'account_id', 'cost_center_id', 'project_id',
        'line_number', 'description', 'debit_amount', 'credit_amount',
        'currency_id', 'exchange_rate', 'foreign_debit', 'foreign_credit',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:3',
        'credit_amount' => 'decimal:3',
        'exchange_rate' => 'decimal:6',
        'foreign_debit' => 'decimal:3',
        'foreign_credit' => 'decimal:3',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class, 'voucher_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
