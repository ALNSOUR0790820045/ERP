<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractRetention extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'retention_type',
        'retention_rate',
        'max_retention_amount',
        'total_retained_amount',
        'released_amount',
        'balance_amount',
        'release_conditions',
        'release_schedule',
        'status',
    ];

    protected $casts = [
        'retention_rate' => 'decimal:2',
        'max_retention_amount' => 'decimal:3',
        'total_retained_amount' => 'decimal:3',
        'released_amount' => 'decimal:3',
        'balance_amount' => 'decimal:3',
    ];

    // العلاقات
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(ContractRetentionRelease::class);
    }

    // الثوابت
    public const RETENTION_TYPES = [
        'performance' => 'حسن التنفيذ',
        'defects_liability' => 'فترة الصيانة',
        'advance_recovery' => 'استرداد الدفعة المقدمة',
    ];

    public const STATUSES = [
        'active' => 'نشط',
        'partially_released' => 'محرر جزئياً',
        'fully_released' => 'محرر بالكامل',
    ];

    public function getRetentionTypeLabelAttribute(): string
    {
        return self::RETENTION_TYPES[$this->retention_type] ?? $this->retention_type;
    }

    /**
     * حساب المحتجز من مستخلص
     */
    public function calculateRetentionFromIPC(float $ipcAmount): float
    {
        $retentionAmount = $ipcAmount * ($this->retention_rate / 100);
        
        // التحقق من الحد الأعلى
        $newTotal = $this->total_retained_amount + $retentionAmount;
        if ($this->max_retention_amount && $newTotal > $this->max_retention_amount) {
            $retentionAmount = max(0, $this->max_retention_amount - $this->total_retained_amount);
        }

        return round($retentionAmount, 3);
    }
}
