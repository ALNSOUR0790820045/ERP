<?php

namespace App\Models\Tenders;

use App\Models\Company;
use App\Models\Tender;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نموذج تصحيحات الأخطاء الحسابية
 * يمثل تصحيحات الأخطاء الحسابية في عروض المناقصين حسب التعليمات للمناقصين
 */
class TenderArithmeticCorrection extends Model
{
    protected $fillable = [
        'tender_id',
        'bidder_id',
        'bidder_name',
        'correction_type',
        'item_number',
        'item_description',
        'original_value',
        'corrected_value',
        'difference',
        'correction_basis',
        'correction_rule',
        'bidder_accepted',
        'notification_date',
        'response_date',
        'bid_rejected_for_refusal',
        'corrected_by',
        'notes',
    ];

    protected $casts = [
        'original_value' => 'decimal:3',
        'corrected_value' => 'decimal:3',
        'difference' => 'decimal:3',
        'bidder_accepted' => 'boolean',
        'bid_rejected_for_refusal' => 'boolean',
        'notification_date' => 'date',
        'response_date' => 'date',
    ];

    // ==========================================
    // الثوابت - أنواع التصحيحات
    // ==========================================

    const TYPE_UNIT_PRICE_VS_TOTAL = 'unit_price_vs_total';
    const TYPE_SUBTOTAL_ADDITION = 'subtotal_addition';
    const TYPE_WORDS_VS_NUMBERS = 'words_vs_numbers';
    const TYPE_MISSING_UNIT_PRICE = 'missing_unit_price';
    const TYPE_DISCOUNT_CALCULATION = 'discount_calculation';
    const TYPE_UNPRICED_ITEM = 'unpriced_item';
    const TYPE_UNCLEAR_PRICE = 'unclear_price';
    const TYPE_FRONT_LOADING = 'front_loading';
    const TYPE_ABNORMALLY_LOW = 'abnormally_low';
    const TYPE_OTHER = 'other';

    /**
     * أنواع التصحيحات وقواعدها
     */
    public static function getCorrectionTypes(): array
    {
        return [
            self::TYPE_UNIT_PRICE_VS_TOTAL => [
                'label' => 'تعارض سعر الوحدة والإجمالي',
                'rule' => 'يتم اعتماد سعر الوحدة ويعدل السعر الإجمالي وفقاً لذلك',
            ],
            self::TYPE_SUBTOTAL_ADDITION => [
                'label' => 'خطأ في جمع المجاميع الفرعية',
                'rule' => 'تعتمد المبالغ الإجمالية الفرعية ويصحح السعر الإجمالي وفقاً لذلك',
            ],
            self::TYPE_WORDS_VS_NUMBERS => [
                'label' => 'تعارض الكلمات والأرقام',
                'rule' => 'يعتمد السعر المحدد بالكلمات إلا إذا وجدت قرينة لاعتماد الأرقام',
            ],
            self::TYPE_MISSING_UNIT_PRICE => [
                'label' => 'سعر وحدة مفقود',
                'rule' => 'يتم احتساب سعر الوحدة من قسمة الإجمالي على الكمية',
            ],
            self::TYPE_DISCOUNT_CALCULATION => [
                'label' => 'حساب الخصم',
                'rule' => 'يتم احتساب الخصم كنسبة من السعر المقروء قبل التصحيح',
            ],
            self::TYPE_UNPRICED_ITEM => [
                'label' => 'بند غير مسعر',
                'rule' => 'البنود غير المسعرة تعتبر محملة على بنود العرض الأخرى وتنفذ بدون مقابل',
            ],
            self::TYPE_UNCLEAR_PRICE => [
                'label' => 'سعر غير واضح',
                'rule' => 'يطبق أعلى سعر ورد عند المناقصين الآخرين، ثم أدنى سعر إذا بقي العرض الأقل',
            ],
            self::TYPE_FRONT_LOADING => [
                'label' => 'أسعار مرتفعة في البداية',
                'rule' => 'يمكن زيادة تأمين حسن التنفيذ حتى 20% أو رفض العرض',
            ],
            self::TYPE_ABNORMALLY_LOW => [
                'label' => 'سعر منخفض بشكل غير طبيعي',
                'rule' => 'يطلب من المناقص تقديم إيضاحات ومبررات وتحليل تفصيلي للأسعار',
            ],
            self::TYPE_OTHER => [
                'label' => 'تصحيح آخر',
                'rule' => 'حسب الحالة',
            ],
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

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
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
     * نوع التصحيح بالعربية
     */
    public function getTypeArabicAttribute(): string
    {
        return self::getCorrectionTypes()[$this->correction_type]['label'] ?? 'غير محدد';
    }

    /**
     * القاعدة المطبقة
     */
    public function getRuleAttribute(): string
    {
        return self::getCorrectionTypes()[$this->correction_type]['rule'] ?? '';
    }

    /**
     * نسبة الفرق
     */
    public function getDifferencePercentageAttribute(): ?float
    {
        if ($this->original_value == 0) {
            return null;
        }
        return ($this->difference / $this->original_value) * 100;
    }

    /**
     * حالة قبول المناقص
     */
    public function getAcceptanceStatusAttribute(): string
    {
        if ($this->bidder_accepted === null) {
            return 'في انتظار الرد';
        }
        return $this->bidder_accepted ? 'مقبول' : 'مرفوض';
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeAccepted($query)
    {
        return $query->where('bidder_accepted', true);
    }

    public function scopeRejected($query)
    {
        return $query->where('bidder_accepted', false);
    }

    public function scopePending($query)
    {
        return $query->whereNull('bidder_accepted');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('correction_type', $type);
    }

    public function scopeByBidder($query, int $bidderId)
    {
        return $query->where('bidder_id', $bidderId);
    }

    // ==========================================
    // Methods
    // ==========================================

    /**
     * تسجيل قبول المناقص
     */
    public function recordAcceptance(): void
    {
        $this->update([
            'bidder_accepted' => true,
            'response_date' => now(),
        ]);
    }

    /**
     * تسجيل رفض المناقص (يؤدي لمصادرة التأمين)
     */
    public function recordRejection(): void
    {
        $this->update([
            'bidder_accepted' => false,
            'response_date' => now(),
            'bid_rejected_for_refusal' => true,
        ]);
    }

    /**
     * حساب الفرق
     */
    public function calculateDifference(): void
    {
        $this->difference = $this->corrected_value - $this->original_value;
        $this->save();
    }

    /**
     * الحصول على إجمالي التصحيحات لمناقص
     */
    public static function getTotalCorrectionForBidder(int $tenderId, int $bidderId): float
    {
        return self::where('tender_id', $tenderId)
            ->where('bidder_id', $bidderId)
            ->sum('difference');
    }

    /**
     * إنشاء تصحيح جديد
     */
    public static function createCorrection(
        Tender $tender,
        int $bidderId,
        string $bidderName,
        string $type,
        float $originalValue,
        float $correctedValue,
        string $basis,
        ?string $itemNumber = null,
        ?string $itemDescription = null
    ): self {
        $typeInfo = self::getCorrectionTypes()[$type] ?? null;
        
        return self::create([
            'tender_id' => $tender->id,
            'bidder_id' => $bidderId,
            'bidder_name' => $bidderName,
            'correction_type' => $type,
            'item_number' => $itemNumber,
            'item_description' => $itemDescription,
            'original_value' => $originalValue,
            'corrected_value' => $correctedValue,
            'difference' => $correctedValue - $originalValue,
            'correction_basis' => $basis,
            'correction_rule' => $typeInfo['rule'] ?? null,
            'notification_date' => now(),
            'corrected_by' => auth()->id(),
        ]);
    }
}
