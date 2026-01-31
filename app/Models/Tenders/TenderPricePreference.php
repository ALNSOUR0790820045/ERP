<?php

namespace App\Models\Tenders;

use App\Models\Company;
use App\Models\Tender;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نموذج الأفضليات السعرية
 * يمثل الأفضليات السعرية للمناقصين حسب قرارات مجلس الوزراء الأردني
 */
class TenderPricePreference extends Model
{
    protected $fillable = [
        'tender_id',
        'bidder_id',
        'bidder_name',
        'preference_type',
        'preference_percentage',
        'original_price',
        'adjusted_price',
        'discount_amount',
        'eligibility_proof',
        'eligibility_documents',
        'is_verified',
        'verified_by',
        'verified_at',
        'is_applied',
        'notes',
    ];

    protected $casts = [
        'preference_percentage' => 'decimal:2',
        'original_price' => 'decimal:3',
        'adjusted_price' => 'decimal:3',
        'discount_amount' => 'decimal:3',
        'is_verified' => 'boolean',
        'is_applied' => 'boolean',
        'verified_at' => 'datetime',
    ];

    // ==========================================
    // الثوابت - أنواع الأفضليات
    // ==========================================

    const TYPE_SME = 'sme';
    const TYPE_WOMEN_OWNERSHIP = 'women_ownership';
    const TYPE_YOUTH_OWNERSHIP = 'youth_ownership';
    const TYPE_WOMEN_MANAGEMENT = 'women_management';
    const TYPE_YOUTH_MANAGEMENT = 'youth_management';
    const TYPE_DISABILITY = 'disability';

    /**
     * أنواع الأفضليات ونسبها
     */
    public static function getPreferenceTypes(): array
    {
        return [
            self::TYPE_SME => [
                'label' => 'المنشآت الصغيرة والمتوسطة',
                'percentage' => null, // حسب قرارات لجنة سياسات الشراء
                'description' => 'أفضلية للمنشآت الصغيرة والمتوسطة وفق أسس لجنة سياسات الشراء',
            ],
            self::TYPE_WOMEN_OWNERSHIP => [
                'label' => 'ملكية المرأة (>51%)',
                'percentage' => 2.0,
                'description' => 'منشأة تمتلك فيها النساء حصصاً لا تقل نسبتها عن 51%',
            ],
            self::TYPE_YOUTH_OWNERSHIP => [
                'label' => 'ملكية الشباب (>51%)',
                'percentage' => 2.0,
                'description' => 'منشأة يمتلك فيها الشباب حصصاً لا تقل نسبتها عن 51%',
            ],
            self::TYPE_WOMEN_MANAGEMENT => [
                'label' => 'إدارة المرأة',
                'percentage' => 2.0,
                'description' => 'منشأة يكون فيها منصب المدير العام أو المفوض بالتوقيع منوطاً بالنساء لمدة لا تقل عن سنتين',
            ],
            self::TYPE_YOUTH_MANAGEMENT => [
                'label' => 'إدارة الشباب',
                'percentage' => 2.0,
                'description' => 'منشأة يكون فيها منصب المدير العام أو المفوض بالتوقيع منوطاً بالشباب لمدة لا تقل عن سنتين',
            ],
            self::TYPE_DISABILITY => [
                'label' => 'ذوي الإعاقة (>51%)',
                'percentage' => 1.0,
                'description' => 'منشأة يمتلك فيها ذوو الإعاقة حصصاً لا تقل نسبتها عن 51%',
            ],
        ];
    }

    /**
     * متطلبات الأهلية للأفضلية
     */
    public static function getEligibilityRequirements(): array
    {
        return [
            'مضى على تسجيل المنشأة مدة لا تقل عن سنتين',
            'للمنشأة نشاط تجاري فعلي',
            'تقارير مالية معتمدة من محاسب قانوني',
            'المنشأة مسجلة على البوابة الإلكترونية',
            'شهادة مشاركة في البرنامج التعريفي لنظام المشتريات الحكومية',
            'المنشأة مصنفة لدى دائرة العطاءات الحكومية',
        ];
    }

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

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ==========================================
    // Accessors
    // ==========================================

    /**
     * اسم المناقص
     */
    public function getBidderDisplayNameAttribute(): string
    {
        return $this->bidder?->name ?? $this->bidder_name ?? 'غير محدد';
    }

    /**
     * نوع الأفضلية بالعربية
     */
    public function getTypeArabicAttribute(): string
    {
        return self::getPreferenceTypes()[$this->preference_type]['label'] ?? 'غير محدد';
    }

    /**
     * وصف الأفضلية
     */
    public function getTypeDescriptionAttribute(): string
    {
        return self::getPreferenceTypes()[$this->preference_type]['description'] ?? '';
    }

    /**
     * نسبة الأفضلية الافتراضية
     */
    public function getDefaultPercentageAttribute(): ?float
    {
        return self::getPreferenceTypes()[$this->preference_type]['percentage'] ?? null;
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeApplied($query)
    {
        return $query->where('is_applied', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('preference_type', $type);
    }

    public function scopeByBidder($query, int $bidderId)
    {
        return $query->where('bidder_id', $bidderId);
    }

    // ==========================================
    // Methods
    // ==========================================

    /**
     * حساب السعر المعدل للتقييم
     */
    public function calculateAdjustedPrice(): void
    {
        if ($this->original_price && $this->preference_percentage) {
            $this->discount_amount = $this->original_price * ($this->preference_percentage / 100);
            $this->adjusted_price = $this->original_price - $this->discount_amount;
            $this->save();
        }
    }

    /**
     * التحقق من الأفضلية
     */
    public function verify(int $userId): void
    {
        $this->update([
            'is_verified' => true,
            'verified_by' => $userId,
            'verified_at' => now(),
        ]);
    }

    /**
     * تطبيق الأفضلية
     */
    public function apply(): void
    {
        if (!$this->is_verified) {
            throw new \Exception('يجب التحقق من الأفضلية قبل تطبيقها');
        }
        
        $this->update(['is_applied' => true]);
    }

    /**
     * إلغاء الأفضلية
     */
    public function revoke(?string $reason = null): void
    {
        $this->update([
            'is_verified' => false,
            'is_applied' => false,
            'notes' => $reason ?? $this->notes,
        ]);
    }

    /**
     * حساب الأفضليات لجميع المناقصين في عطاء
     */
    public static function calculateAllPreferences(Tender $tender): void
    {
        $preferences = self::where('tender_id', $tender->id)
            ->where('is_verified', true)
            ->get();
        
        foreach ($preferences as $preference) {
            $preference->calculateAdjustedPrice();
        }
    }

    /**
     * الحصول على إجمالي نسبة الأفضلية لمناقص
     * (في حالة الائتلاف تمنح الأفضلية مرة واحدة فقط)
     */
    public static function getTotalPreferenceForBidder(int $tenderId, int $bidderId): float
    {
        return self::where('tender_id', $tenderId)
            ->where('bidder_id', $bidderId)
            ->where('is_verified', true)
            ->sum('preference_percentage');
    }

    /**
     * إنشاء أفضلية جديدة
     */
    public static function createPreference(
        Tender $tender,
        int $bidderId,
        string $bidderName,
        string $type,
        float $originalPrice
    ): self {
        $typeInfo = self::getPreferenceTypes()[$type] ?? null;
        
        if (!$typeInfo) {
            throw new \Exception('نوع الأفضلية غير صالح');
        }
        
        $percentage = $typeInfo['percentage'];
        $discountAmount = $originalPrice * ($percentage / 100);
        $adjustedPrice = $originalPrice - $discountAmount;
        
        return self::create([
            'tender_id' => $tender->id,
            'bidder_id' => $bidderId,
            'bidder_name' => $bidderName,
            'preference_type' => $type,
            'preference_percentage' => $percentage,
            'original_price' => $originalPrice,
            'adjusted_price' => $adjustedPrice,
            'discount_amount' => $discountAmount,
        ]);
    }
}
