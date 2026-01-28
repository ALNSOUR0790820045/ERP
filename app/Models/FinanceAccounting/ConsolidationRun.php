<?php

namespace App\Models\FinanceAccounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsolidationRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'consolidation_group_id',
        'fiscal_period_id',
        'run_number',
        'consolidation_date',
        'status',
        'exchange_rates_used',
        'elimination_entries',
        'translation_adjustments',
        'total_assets',
        'total_liabilities',
        'total_equity',
        'net_income',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'consolidation_date' => 'date',
        'exchange_rates_used' => 'array',
        'elimination_entries' => 'array',
        'translation_adjustments' => 'array',
        'total_assets' => 'decimal:2',
        'total_liabilities' => 'decimal:2',
        'total_equity' => 'decimal:2',
        'net_income' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function consolidationGroup(): BelongsTo
    {
        return $this->belongsTo(ConsolidationGroup::class);
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function intercompanyTransactions(): HasMany
    {
        return $this->hasMany(IntercompanyTransaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function generateRunNumber(): string
    {
        $prefix = 'CON';
        $year = date('Y');
        $lastRun = static::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
        $sequence = $lastRun ? intval(substr($lastRun->run_number, -5)) + 1 : 1;
        return $prefix . $year . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }
}
