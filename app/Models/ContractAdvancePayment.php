<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractAdvancePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'advance_type',
        'advance_number',
        'advance_date',
        'advance_percentage',
        'advance_amount',
        'bank_guarantee_number',
        'bank_guarantee_amount',
        'bank_guarantee_expiry',
        'recovered_amount',
        'balance_amount',
        'recovery_rate',
        'status',
        'payment_voucher_id',
    ];

    protected $casts = [
        'advance_date' => 'date',
        'advance_percentage' => 'decimal:2',
        'advance_amount' => 'decimal:3',
        'bank_guarantee_amount' => 'decimal:3',
        'bank_guarantee_expiry' => 'date',
        'recovered_amount' => 'decimal:3',
        'balance_amount' => 'decimal:3',
        'recovery_rate' => 'decimal:2',
    ];

    // العلاقات
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function paymentVoucher(): BelongsTo
    {
        return $this->belongsTo(PaymentVoucher::class);
    }

    public function recoveries(): HasMany
    {
        return $this->hasMany(ContractAdvanceRecovery::class);
    }

    // الثوابت
    public const ADVANCE_TYPES = [
        'mobilization' => 'دفعة تعبئة',
        'materials' => 'دفعة مواد',
        'equipment' => 'دفعة معدات',
    ];

    public const STATUSES = [
        'pending' => 'معلق',
        'paid' => 'مدفوع',
        'partially_recovered' => 'مسترد جزئياً',
        'fully_recovered' => 'مسترد بالكامل',
    ];

    public function getAdvanceTypeLabelAttribute(): string
    {
        return self::ADVANCE_TYPES[$this->advance_type] ?? $this->advance_type;
    }

    /**
     * حساب الاسترداد من مستخلص
     */
    public function calculateRecoveryFromIPC(float $ipcAmount): float
    {
        if ($this->balance_amount <= 0) {
            return 0;
        }

        $recoveryAmount = $ipcAmount * ($this->recovery_rate / 100);
        
        // لا يتجاوز الرصيد المتبقي
        $recoveryAmount = min($recoveryAmount, $this->balance_amount);

        return round($recoveryAmount, 3);
    }
}
