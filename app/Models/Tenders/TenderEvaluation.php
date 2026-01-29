<?php

namespace App\Models\Tenders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تقييم العطاء (Go/No-Go المفصل)
 * Tender Evaluation
 */
class TenderEvaluation extends Model
{
    protected $fillable = [
        'tender_id',
        'decision_id',
        // معايير التقييم
        'strategic_alignment_score',
        'strategic_alignment_notes',
        'technical_capability_score',
        'technical_capability_notes',
        'human_resources_score',
        'human_resources_notes',
        'equipment_availability_score',
        'equipment_availability_notes',
        'financial_capacity_score',
        'financial_capacity_notes',
        'similar_experience_score',
        'similar_experience_notes',
        'owner_relationship_score',
        'owner_relationship_notes',
        'competition_level_score',
        'competition_level_notes',
        'profit_margin_score',
        'expected_profit_percentage',
        'risk_level_score',
        'risk_level_notes',
        'location_score',
        'location_notes',
        'timeline_feasibility_score',
        'timeline_notes',
        // النتائج
        'total_weighted_score',
        'passing_threshold',
        'recommendation',
        'conditions',
        'evaluated_by',
        'evaluated_at',
    ];

    protected $casts = [
        'strategic_alignment_score' => 'integer',
        'technical_capability_score' => 'integer',
        'human_resources_score' => 'integer',
        'equipment_availability_score' => 'integer',
        'financial_capacity_score' => 'integer',
        'similar_experience_score' => 'integer',
        'owner_relationship_score' => 'integer',
        'competition_level_score' => 'integer',
        'profit_margin_score' => 'integer',
        'risk_level_score' => 'integer',
        'location_score' => 'integer',
        'timeline_feasibility_score' => 'integer',
        'total_weighted_score' => 'decimal:2',
        'passing_threshold' => 'decimal:2',
        'expected_profit_percentage' => 'decimal:2',
        'evaluated_at' => 'datetime',
    ];

    // التوصيات
    public const RECOMMENDATIONS = [
        'strongly_go' => 'مشاركة بقوة',
        'go' => 'مشاركة',
        'conditional_go' => 'مشاركة مشروطة',
        'no_go' => 'عدم مشاركة',
        'defer' => 'تأجيل القرار',
    ];

    // معايير التقييم وأوزانها
    public const CRITERIA_WEIGHTS = [
        'strategic_alignment' => 10,
        'technical_capability' => 12,
        'human_resources' => 10,
        'equipment_availability' => 8,
        'financial_capacity' => 10,
        'similar_experience' => 12,
        'owner_relationship' => 8,
        'competition_level' => 10,
        'profit_margin' => 8,
        'risk_level' => 5,
        'location' => 4,
        'timeline_feasibility' => 3,
    ];

    // العلاقات
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(TenderDecision::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    // Methods
    public function calculateWeightedScore(): float
    {
        $totalScore = 0;
        $totalWeight = 0;

        foreach (self::CRITERIA_WEIGHTS as $criterion => $weight) {
            $scoreField = $criterion . '_score';
            $score = $this->$scoreField;
            
            if ($score !== null) {
                $totalScore += ($score / 5) * $weight; // Score is 1-5, normalized
                $totalWeight += $weight;
            }
        }

        return $totalWeight > 0 ? ($totalScore / $totalWeight) * 100 : 0;
    }

    public function updateWeightedScore(): void
    {
        $this->update([
            'total_weighted_score' => $this->calculateWeightedScore(),
        ]);
    }

    public function getRecommendation(): string
    {
        $score = $this->total_weighted_score ?? $this->calculateWeightedScore();
        
        if ($score >= 80) return 'strongly_go';
        if ($score >= 65) return 'go';
        if ($score >= 50) return 'conditional_go';
        if ($score >= 35) return 'defer';
        return 'no_go';
    }

    // Accessors
    public function getRecommendationLabelAttribute(): ?string
    {
        return self::RECOMMENDATIONS[$this->recommendation] ?? $this->recommendation;
    }

    public function getIsPassingAttribute(): bool
    {
        return ($this->total_weighted_score ?? 0) >= ($this->passing_threshold ?? 60);
    }

    public function getCriteriaScoresAttribute(): array
    {
        return [
            'strategic_alignment' => $this->strategic_alignment_score,
            'technical_capability' => $this->technical_capability_score,
            'human_resources' => $this->human_resources_score,
            'equipment_availability' => $this->equipment_availability_score,
            'financial_capacity' => $this->financial_capacity_score,
            'similar_experience' => $this->similar_experience_score,
            'owner_relationship' => $this->owner_relationship_score,
            'competition_level' => $this->competition_level_score,
            'profit_margin' => $this->profit_margin_score,
            'risk_level' => $this->risk_level_score,
            'location' => $this->location_score,
            'timeline_feasibility' => $this->timeline_feasibility_score,
        ];
    }
}
