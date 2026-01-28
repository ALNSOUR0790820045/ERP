<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * معايير التقييم الفني
 * Tender Technical Criteria - القسم الثالث من وثيقة العطاءات
 */
class TenderTechnicalCriterion extends Model
{
    protected $table = 'tender_technical_criteria';

    protected $fillable = [
        'tender_id',
        'category',
        'criterion_ar',
        'criterion_en',
        'description',
        'weight',
        'max_score',
        'minimum_score',
        'scoring_guide',
        'required_evidence',
        'is_pass_fail',
        'is_mandatory',
        'sort_order',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'max_score' => 'decimal:2',
        'minimum_score' => 'decimal:2',
        'scoring_guide' => 'array',
        'required_evidence' => 'array',
        'is_pass_fail' => 'boolean',
        'is_mandatory' => 'boolean',
    ];

    // فئات المعايير
    public const CATEGORIES = [
        'experience' => 'الخبرة العامة',
        'similar_experience' => 'الخبرة في مشاريع مماثلة',
        'personnel' => 'الكوادر الفنية',
        'equipment' => 'المعدات',
        'methodology' => 'منهجية التنفيذ',
        'work_program' => 'البرنامج الزمني',
        'quality_plan' => 'خطة الجودة',
        'safety_plan' => 'خطة السلامة',
        'financial_capability' => 'القدرة المالية',
        'other' => 'أخرى',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(TenderTechnicalEvaluation::class, 'criterion_id');
    }

    public function getCategoryNameAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
