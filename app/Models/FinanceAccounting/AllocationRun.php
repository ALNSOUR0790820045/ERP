<?php

namespace App\Models\FinanceAccounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AllocationRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'allocation_rule_id',
        'fiscal_period_id',
        'run_number',
        'allocation_date',
        'total_allocated',
        'status',
        'journal_voucher_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'allocation_date' => 'date',
        'total_allocated' => 'decimal:2',
    ];

    public function allocationRule(): BelongsTo
    {
        return $this->belongsTo(AllocationRule::class);
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(AllocationRunDetail::class);
    }

    public function journalVoucher(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateRunNumber(): string
    {
        $prefix = 'ALLOC';
        $year = date('Y');
        $lastRun = static::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
        $sequence = $lastRun ? intval(substr($lastRun->run_number, -5)) + 1 : 1;
        return $prefix . $year . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }
}
