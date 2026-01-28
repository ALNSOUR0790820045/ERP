<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * كفالات العطاء
 * Tender Bonds - تأمينات وكفالات العطاء حسب الوثيقة الأردنية
 */
class TenderBond extends Model
{
    protected $fillable = [
        'tender_id',
        'bond_type',
        'bond_number',
        'issue_date',
        'expiry_date',
        'amount',
        'currency_id',
        'issuer_type',
        'issuer_name',
        'issuer_branch',
        'issuer_address',
        'issuer_contact',
        'beneficiary_name',
        'beneficiary_address',
        'document_path',
        'status',
        'extension_count',
        'original_expiry_date',
        'issuance_fee',
        'commission_rate',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'original_expiry_date' => 'date',
        'amount' => 'decimal:3',
        'issuance_fee' => 'decimal:2',
        'commission_rate' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'extension_count' => 'integer',
    ];

    // أنواع الكفالات
    public const BOND_TYPES = [
        'bid_security' => 'تأمين دخول العطاء',
        'performance_bond' => 'كفالة حسن التنفيذ',
        'advance_payment_bond' => 'كفالة الدفعة المقدمة',
        'retention_bond' => 'كفالة المحتجزات',
        'maintenance_bond' => 'كفالة الصيانة',
    ];

    // أنواع الجهة المصدرة
    public const ISSUER_TYPES = [
        'bank' => 'بنك',
        'insurance_company' => 'شركة تأمين',
    ];

    // حالات الكفالة
    public const STATUSES = [
        'draft' => 'مسودة',
        'requested' => 'مطلوبة',
        'issued' => 'صادرة',
        'submitted' => 'مقدمة',
        'released' => 'محررة',
        'extended' => 'ممددة',
        'claimed' => 'مطالب بها',
        'expired' => 'منتهية',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(TenderBondExtension::class, 'bond_id');
    }

    public function getBondTypeNameAttribute(): string
    {
        return self::BOND_TYPES[$this->bond_type] ?? $this->bond_type;
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * هل الكفالة قريبة من الانتهاء (خلال 30 يوم)
     */
    public function getIsExpiringAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->diffInDays(now()) <= 30;
    }

    /**
     * هل الكفالة منتهية
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * الأيام المتبقية للانتهاء
     */
    public function getDaysToExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }
        return now()->diffInDays($this->expiry_date, false);
    }
}
