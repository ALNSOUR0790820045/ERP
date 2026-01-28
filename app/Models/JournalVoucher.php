<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JournalVoucher extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'fiscal_year_id', 'fiscal_period_id',
        'voucher_number', 'voucher_date', 'voucher_type', 'description',
        'reference_type', 'reference_id', 'reference_number',
        'total_debit', 'total_credit', 'status', 'is_posted',
        'posted_at', 'posted_by', 'created_by',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'total_debit' => 'decimal:3',
        'total_credit' => 'decimal:3',
        'is_posted' => 'boolean',
        'posted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalVoucherLine::class, 'voucher_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
