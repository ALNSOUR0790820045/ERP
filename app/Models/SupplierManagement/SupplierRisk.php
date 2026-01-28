<?php

namespace App\Models\SupplierManagement;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierRisk extends Model
{
    protected $fillable = [
        'supplier_id', 'risk_category', 'risk_type', 'title', 'description',
        'likelihood', 'impact', 'risk_level', 'risk_score', 'status',
        'mitigation_strategy', 'contingency_plan', 'owner_id',
        'identified_date', 'review_date', 'resolved_date', 'metadata',
    ];

    protected $casts = [
        'identified_date' => 'date', 'review_date' => 'date', 'resolved_date' => 'date',
        'risk_score' => 'decimal:2', 'metadata' => 'array',
    ];

    const CATEGORY_FINANCIAL = 'financial';
    const CATEGORY_OPERATIONAL = 'operational';
    const CATEGORY_COMPLIANCE = 'compliance';
    const CATEGORY_REPUTATION = 'reputation';
    const CATEGORY_STRATEGIC = 'strategic';

    const LEVEL_LOW = 'low';
    const LEVEL_MEDIUM = 'medium';
    const LEVEL_HIGH = 'high';
    const LEVEL_CRITICAL = 'critical';

    const STATUS_IDENTIFIED = 'identified';
    const STATUS_ASSESSED = 'assessed';
    const STATUS_MITIGATING = 'mitigating';
    const STATUS_MONITORING = 'monitoring';
    const STATUS_RESOLVED = 'resolved';

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }

    public function scopeActive($q) { return $q->whereNotIn('status', [self::STATUS_RESOLVED]); }
    public function scopeHighRisk($q) { return $q->whereIn('risk_level', [self::LEVEL_HIGH, self::LEVEL_CRITICAL]); }
    public function scopeNeedsReview($q) { return $q->whereNotNull('review_date')->where('review_date', '<=', now()); }

    public function calculateScore(): float {
        $likelihoodScores = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        $impactScores = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        return ($likelihoodScores[$this->likelihood] ?? 2) * ($impactScores[$this->impact] ?? 2);
    }

    public function resolve(string $notes = null): void {
        $this->update(['status' => self::STATUS_RESOLVED, 'resolved_date' => now()]);
    }
}
