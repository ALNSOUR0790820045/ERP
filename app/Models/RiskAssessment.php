<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAssessment extends Model
{
    protected $fillable = [
        'project_id', 'hazard_category_id', 'assessment_number', 'assessment_date',
        'work_activity', 'location', 'hazard_description', 'potential_consequences',
        'likelihood', 'severity', 'risk_score', 'risk_level', 'existing_controls',
        'additional_controls', 'residual_likelihood', 'residual_severity',
        'residual_risk_score', 'status', 'assessed_by', 'approved_by',
    ];

    protected $casts = [
        'assessment_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function hazardCategory(): BelongsTo
    {
        return $this->belongsTo(HazardCategory::class);
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function calculateRiskScore(): void
    {
        $this->risk_score = $this->likelihood * $this->severity;
        
        if ($this->risk_score <= 4) {
            $this->risk_level = 'low';
        } elseif ($this->risk_score <= 9) {
            $this->risk_level = 'medium';
        } elseif ($this->risk_score <= 15) {
            $this->risk_level = 'high';
        } else {
            $this->risk_level = 'extreme';
        }
    }
}
