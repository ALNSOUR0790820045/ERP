<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * العرض الفني المنظم
 * Tender Technical Proposal
 */
class TenderTechnicalProposal extends Model
{
    protected $fillable = [
        'tender_id',
        // الملفات
        'company_profile_path',
        'organization_chart_path',
        'method_statement_path',
        'work_program_path',
        'quality_plan_path',
        'safety_plan_path',
        'environmental_plan_path',
        // القوائم المالية
        'financial_statements_path',
        'bank_reference_path',
        'average_annual_turnover',
        'current_liquid_assets',
        // الشهادات
        'classification_certificate_path',
        'classification_category',
        'classification_expiry',
        'registration_certificate_path',
        'tax_clearance_path',
        'tax_clearance_date',
        'social_security_clearance_path',
        'social_security_clearance_date',
        // بيانات الإنجاز
        'total_similar_projects',
        'total_similar_value',
        // الحالة
        'status',
        'completeness_percentage',
    ];

    protected $casts = [
        'average_annual_turnover' => 'decimal:3',
        'current_liquid_assets' => 'decimal:3',
        'classification_expiry' => 'date',
        'tax_clearance_date' => 'date',
        'social_security_clearance_date' => 'date',
        'total_similar_projects' => 'integer',
        'total_similar_value' => 'decimal:3',
        'completeness_percentage' => 'decimal:2',
    ];

    public const STATUSES = [
        'draft' => 'مسودة',
        'ready' => 'جاهز',
        'submitted' => 'مقدم',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function similarProjects(): HasMany
    {
        return $this->hasMany(TenderSimilarProject::class, 'technical_proposal_id');
    }

    public function keyPersonnel(): HasMany
    {
        return $this->hasMany(TenderKeyPersonnel::class, 'technical_proposal_id');
    }

    public function equipmentList(): HasMany
    {
        return $this->hasMany(TenderEquipmentListItem::class, 'technical_proposal_id');
    }

    /**
     * حساب نسبة الاكتمال
     */
    public function calculateCompleteness(): float
    {
        $requiredFields = [
            'company_profile_path',
            'method_statement_path',
            'work_program_path',
            'classification_certificate_path',
            'registration_certificate_path',
            'tax_clearance_path',
            'social_security_clearance_path',
        ];

        $filled = 0;
        foreach ($requiredFields as $field) {
            if (!empty($this->$field)) {
                $filled++;
            }
        }

        return ($filled / count($requiredFields)) * 100;
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
