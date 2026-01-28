<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تقييم المناقصين فنياً
 * Tender Technical Evaluation
 */
class TenderTechnicalEvaluation extends Model
{
    protected $fillable = [
        'tender_id',
        'competitor_id',
        'criterion_id',
        'score',
        'weighted_score',
        'is_passed',
        'justification',
        'evaluated_by',
        'evaluated_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'weighted_score' => 'decimal:2',
        'is_passed' => 'boolean',
        'evaluated_at' => 'datetime',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(TenderCompetitor::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(TenderTechnicalCriterion::class);
    }

    public function evaluatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }
}
