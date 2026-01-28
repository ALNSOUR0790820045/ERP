<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    protected $fillable = [
        'company_id', 'parent_id', 'code', 'name_ar', 'name_en',
        'account_type', 'account_nature', 'level', 'is_header',
        'is_bank_account', 'is_cash_account', 'is_active',
    ];

    protected $casts = [
        'is_header' => 'boolean',
        'is_bank_account' => 'boolean',
        'is_cash_account' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalVoucherLine::class, 'account_id');
    }
}
