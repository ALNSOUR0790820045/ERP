<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentVoucher extends Model
{
    protected $fillable = [
        'company_id', 'project_id', 'voucher_number', 'voucher_date',
        'payment_method', 'payee_type', 'payee_id', 'payee_name',
        'amount', 'currency_id', 'exchange_rate', 'bank_account_id',
        'check_number', 'check_date', 'description', 'status',
        'journal_voucher_id', 'created_by',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'check_date' => 'date',
        'amount' => 'decimal:3',
        'exchange_rate' => 'decimal:6',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function payee(): MorphTo
    {
        return $this->morphTo();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function journalVoucher(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
