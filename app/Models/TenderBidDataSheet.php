<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * جدول بيانات المناقصة
 * Tender Bid Data Sheet (BDS) - القسم الثاني من وثيقة العطاءات
 */
class TenderBidDataSheet extends Model
{
    protected $fillable = [
        'tender_id',
        // ITB 1.1 - نطاق المناقصة
        'procuring_entity_ar',
        'procuring_entity_en',
        'beneficiary_entity_ar',
        'beneficiary_entity_en',
        'works_description_ar',
        'works_description_en',
        'number_of_packages',
        // ITB 2.1 - مصدر التمويل
        'funding_source',
        'funding_details',
        'project_name',
        'lender_name',
        // ITB 4.2 - الائتلاف
        'consortium_allowed',
        'max_consortium_members',
        'consortium_requirements',
        // ITB 7.1 - التوضيحات
        'clarification_address',
        'clarification_email',
        'clarification_deadline',
        // ITB 8.1 - زيارة الموقع
        'site_visit_required',
        'site_visit_date',
        'site_visit_location',
        'site_visit_instructions',
        // ITB 11 - لغة العرض
        'bid_language',
        // ITB 14 - عملة العرض
        'bid_currency_id',
        'multiple_currencies_allowed',
        // ITB 18 - فترة سريان العرض
        'bid_validity_days',
        // ITB 19 - تأمين دخول العطاء
        'bid_security_type',
        'bid_security_percentage',
        'bid_security_amount',
        'bid_security_validity_days',
        'bid_security_beneficiary',
        // ITB 22 - تقديم العروض
        'submission_deadline',
        'submission_address',
        'electronic_submission_allowed',
        'electronic_submission_url',
        // ITB 25 - فتح العروض
        'opening_date',
        'opening_location',
        'bidders_allowed_at_opening',
        // ITB 35 - تأمين حسن التنفيذ
        'performance_security_percentage',
        'performance_security_validity_days',
        // ITB 32 - الأفضلية للمنشآت الصغيرة
        'sme_preference_applicable',
        'sme_preference_percentage',
    ];

    protected $casts = [
        'number_of_packages' => 'integer',
        'consortium_allowed' => 'boolean',
        'max_consortium_members' => 'integer',
        'clarification_deadline' => 'datetime',
        'site_visit_required' => 'boolean',
        'site_visit_date' => 'datetime',
        'multiple_currencies_allowed' => 'boolean',
        'bid_validity_days' => 'integer',
        'bid_security_percentage' => 'decimal:2',
        'bid_security_amount' => 'decimal:3',
        'bid_security_validity_days' => 'integer',
        'submission_deadline' => 'datetime',
        'electronic_submission_allowed' => 'boolean',
        'opening_date' => 'datetime',
        'bidders_allowed_at_opening' => 'boolean',
        'performance_security_percentage' => 'decimal:2',
        'performance_security_validity_days' => 'integer',
        'sme_preference_applicable' => 'boolean',
        'sme_preference_percentage' => 'decimal:2',
    ];

    // مصادر التمويل
    public const FUNDING_SOURCES = [
        'general_budget' => 'الموازنة العامة',
        'project_loan' => 'قرض مشروع',
        'grant' => 'منحة',
        'self_funded' => 'تمويل ذاتي',
        'mixed' => 'مختلط',
    ];

    // أنواع تأمين العطاء
    public const SECURITY_TYPES = [
        'bank_guarantee' => 'كفالة بنكية',
        'certified_check' => 'شيك مصدق',
        'either' => 'أي منهما',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function bidCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'bid_currency_id');
    }
}
