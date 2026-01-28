<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * استيفاء متطلبات الأهلية للمناقصين
 * Tender Bidder Eligibility
 */
class TenderBidderEligibility extends Model
{
    protected $table = 'tender_bidder_eligibility';

    protected $fillable = [
        'tender_id',
        'competitor_id',
        'requirement_id',
        'is_met',
        'score',
        'notes',
        'document_path',
        'evaluated_by',
        'evaluated_at',
    ];

    protected $casts = [
        'is_met' => 'boolean',
        'score' => 'decimal:2',
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

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(TenderEligibilityRequirement::class);
    }

    public function evaluatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }
}
