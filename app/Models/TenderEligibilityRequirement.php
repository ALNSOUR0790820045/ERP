<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * متطلبات الأهلية للعطاء
 * Tender Eligibility Requirements - الفقرات 4.1-4.7 من تعليمات المناقصين
 */
class TenderEligibilityRequirement extends Model
{
    protected $fillable = [
        'tender_id',
        'requirement_type',
        'description_ar',
        'description_en',
        'details',
        'is_mandatory',
        'weight',
        'minimum_score',
        'required_documents',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'weight' => 'decimal:2',
        'minimum_score' => 'decimal:2',
        'required_documents' => 'array',
    ];

    // أنواع المتطلبات
    public const TYPES = [
        'classification' => 'التصنيف المطلوب',
        'experience_years' => 'سنوات الخبرة',
        'similar_projects' => 'مشاريع مماثلة',
        'financial_capability' => 'القدرة المالية',
        'technical_capability' => 'القدرة الفنية',
        'legal_status' => 'الوضع القانوني',
        'no_conflict' => 'عدم تعارض المصالح',
        'not_blacklisted' => 'عدم الحرمان',
        'other' => 'أخرى',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function bidderEligibilities(): HasMany
    {
        return $this->hasMany(TenderBidderEligibility::class, 'requirement_id');
    }

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->requirement_type] ?? $this->requirement_type;
    }
}
