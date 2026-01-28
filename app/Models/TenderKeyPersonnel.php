<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * الكوادر الفنية الرئيسية
 * Tender Key Personnel - نموذج الكوادر من نماذج العرض
 */
class TenderKeyPersonnel extends Model
{
    protected $table = 'tender_key_personnel';

    protected $fillable = [
        'technical_proposal_id',
        'position',
        'name',
        'nationality',
        'qualification',
        'specialization',
        'experience_years',
        'similar_experience_years',
        'cv_path',
        'certificate_path',
        'registration_path',
        'is_permanent_employee',
        'employment_proof_path',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'experience_years' => 'integer',
        'similar_experience_years' => 'integer',
        'is_permanent_employee' => 'boolean',
        'sort_order' => 'integer',
    ];

    // المناصب الرئيسية المعتادة
    public const POSITIONS = [
        'project_manager' => 'مدير المشروع',
        'site_engineer' => 'مهندس الموقع',
        'construction_manager' => 'مدير الإنشاءات',
        'quality_engineer' => 'مهندس الجودة',
        'safety_officer' => 'مسؤول السلامة',
        'surveyor' => 'مساح',
        'quantity_surveyor' => 'مهندس كميات',
        'electrical_engineer' => 'مهندس كهربائي',
        'mechanical_engineer' => 'مهندس ميكانيكي',
        'planning_engineer' => 'مهندس تخطيط',
    ];

    public function technicalProposal(): BelongsTo
    {
        return $this->belongsTo(TenderTechnicalProposal::class);
    }

    public function getPositionNameAttribute(): string
    {
        return self::POSITIONS[$this->position] ?? $this->position;
    }
}
