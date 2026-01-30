<?php

namespace App\Models\Tenders;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\Tender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نموذج المقاولين الفرعيين المقترحين
 * يمثل المقاولين الفرعيين الذين يقترحهم المناقص لتنفيذ أجزاء من العقد
 */
class TenderProposedSubcontractor extends Model
{
    protected $fillable = [
        'tender_id',
        'bidder_id',
        'bidder_name',
        'subcontractor_id',
        'subcontractor_name',
        'subcontractor_classification',
        'work_scope',
        'work_percentage',
        'work_value',
        'is_from_governorate',
        'governorate',
        'is_specialized',
        'specialization',
        'is_approved',
        'approval_notes',
        'qualifications',
        'experience',
    ];

    protected $casts = [
        'work_percentage' => 'decimal:2',
        'work_value' => 'decimal:3',
        'is_from_governorate' => 'boolean',
        'is_specialized' => 'boolean',
        'is_approved' => 'boolean',
    ];

    // ==========================================
    // الثوابت
    // ==========================================

    const MAX_SUBCONTRACT_PERCENTAGE = 33; // الحد الأقصى 33% من قيمة العقد
    const MIN_LOCAL_PERCENTAGE = 10; // الحد الأدنى لنسبة أبناء المحافظة

    // ==========================================
    // العلاقات
    // ==========================================

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function bidder(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'bidder_id');
    }

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'subcontractor_id');
    }

    // ==========================================
    // Accessors
    // ==========================================

    /**
     * اسم المقاول الفرعي
     */
    public function getSubcontractorDisplayNameAttribute(): string
    {
        return $this->subcontractor?->name ?? $this->subcontractor_name ?? 'غير محدد';
    }

    /**
     * اسم المناقص
     */
    public function getBidderDisplayNameAttribute(): string
    {
        return $this->bidder?->name ?? $this->bidder_name ?? 'غير محدد';
    }

    /**
     * حالة الموافقة بالعربية
     */
    public function getApprovalStatusAttribute(): string
    {
        if ($this->is_approved === null) {
            return 'قيد المراجعة';
        }
        return $this->is_approved ? 'موافق عليه' : 'مرفوض';
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeRejected($query)
    {
        return $query->where('is_approved', false);
    }

    public function scopePending($query)
    {
        return $query->whereNull('is_approved');
    }

    public function scopeFromGovernorate($query)
    {
        return $query->where('is_from_governorate', true);
    }

    public function scopeSpecialized($query)
    {
        return $query->where('is_specialized', true);
    }

    public function scopeByBidder($query, int $bidderId)
    {
        return $query->where('bidder_id', $bidderId);
    }

    // ==========================================
    // Methods
    // ==========================================

    /**
     * الموافقة على المقاول الفرعي
     */
    public function approve(string $notes = null): void
    {
        $this->update([
            'is_approved' => true,
            'approval_notes' => $notes,
        ]);
    }

    /**
     * رفض المقاول الفرعي
     */
    public function reject(string $reason): void
    {
        $this->update([
            'is_approved' => false,
            'approval_notes' => $reason,
        ]);
    }

    /**
     * حساب إجمالي نسبة المقاولة الفرعية لمناقص
     */
    public static function getTotalSubcontractPercentage(int $tenderId, int $bidderId): float
    {
        return self::where('tender_id', $tenderId)
            ->where('bidder_id', $bidderId)
            ->sum('work_percentage');
    }

    /**
     * حساب نسبة المقاولين من المحافظة
     */
    public static function getLocalSubcontractPercentage(int $tenderId, int $bidderId): float
    {
        return self::where('tender_id', $tenderId)
            ->where('bidder_id', $bidderId)
            ->where('is_from_governorate', true)
            ->sum('work_percentage');
    }

    /**
     * التحقق من صحة نسب المقاولة الفرعية
     */
    public static function validateSubcontractPercentages(int $tenderId, int $bidderId): array
    {
        $errors = [];
        
        $totalPercentage = self::getTotalSubcontractPercentage($tenderId, $bidderId);
        $localPercentage = self::getLocalSubcontractPercentage($tenderId, $bidderId);
        
        if ($totalPercentage > self::MAX_SUBCONTRACT_PERCENTAGE) {
            $errors[] = sprintf(
                'إجمالي نسبة المقاولة الفرعية (%.2f%%) تتجاوز الحد الأقصى المسموح (%.2f%%)',
                $totalPercentage,
                self::MAX_SUBCONTRACT_PERCENTAGE
            );
        }
        
        if ($localPercentage < self::MIN_LOCAL_PERCENTAGE && $totalPercentage > 0) {
            $errors[] = sprintf(
                'نسبة المقاولين من المحافظة (%.2f%%) أقل من الحد الأدنى المطلوب (%.2f%%)',
                $localPercentage,
                self::MIN_LOCAL_PERCENTAGE
            );
        }
        
        return $errors;
    }
}
