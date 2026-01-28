<?php

namespace App\Models\SupplierManagement;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierRiskAssessment extends Model
{
    protected $fillable = [
        'supplier_id', 'assessment_code', 'assessment_date', 'assessor_id', 'assessment_type',
        'financial_score', 'operational_score', 'compliance_score', 'reputation_score',
        'financial_weight', 'operational_weight', 'compliance_weight', 'reputation_weight',
        'overall_score', 'risk_level', 'status', 'findings', 'recommendations',
        'action_plan', 'next_assessment_date', 'approved_by', 'approved_at', 'metadata',
    ];

    protected $casts = [
        'assessment_date' => 'date', 'next_assessment_date' => 'date', 'approved_at' => 'datetime',
        'financial_score' => 'decimal:2', 'operational_score' => 'decimal:2',
        'compliance_score' => 'decimal:2', 'reputation_score' => 'decimal:2',
        'overall_score' => 'decimal:2', 'financial_weight' => 'decimal:2',
        'operational_weight' => 'decimal:2', 'compliance_weight' => 'decimal:2',
        'reputation_weight' => 'decimal:2', 'metadata' => 'array',
    ];

    const TYPE_INITIAL = 'initial';
    const TYPE_PERIODIC = 'periodic';
    const TYPE_TRIGGERED = 'triggered';
    const TYPE_RENEWAL = 'renewal';

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function assessor(): BelongsTo { return $this->belongsTo(User::class, 'assessor_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function calculateOverallScore(): float {
        return (($this->financial_score * $this->financial_weight) +
                ($this->operational_score * $this->operational_weight) +
                ($this->compliance_score * $this->compliance_weight) +
                ($this->reputation_score * $this->reputation_weight)) / 
               (($this->financial_weight + $this->operational_weight + 
                 $this->compliance_weight + $this->reputation_weight) ?: 1);
    }

    public function determineRiskLevel(): string {
        $score = $this->overall_score ?? $this->calculateOverallScore();
        if ($score >= 80) return 'low';
        if ($score >= 60) return 'medium';
        if ($score >= 40) return 'high';
        return 'critical';
    }

    public function approve(User $approver): void {
        $this->update(['status' => 'approved', 'approved_by' => $approver->id, 'approved_at' => now()]);
        $this->supplier->update(['risk_level' => $this->risk_level, 'risk_score' => $this->overall_score]);
    }

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->assessment_code)) {
                $model->assessment_code = 'SRA-' . date('Ymd') . '-' . str_pad(static::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
