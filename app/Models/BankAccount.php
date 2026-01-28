<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    protected $fillable = [
        'company_id', 'account_id', 'currency_id', 'bank_name', 'branch_name',
        'account_number', 'iban', 'swift_code', 'account_type',
        'opening_balance', 'current_balance', 'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:3',
        'current_balance' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
