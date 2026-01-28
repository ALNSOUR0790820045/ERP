<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * قرار الإحالة
 * Tender Award Decision - قرار الترسية
 */
class TenderAwardDecision extends Model
{
    protected $fillable = [
        'tender_id',
        'decision_type',
        'decision_date',
        'winner_competitor_id',
        'award_amount',
        'currency_id',
        'justification_ar',
        'justification_en',
        'preliminary_announcement_date',
        'standstill_period_days',
        'standstill_end_date',
        'objections_received',
        'objections_details',
        'final_decision_date',
        'final_decision_notes',
        'award_letter_number',
        'award_letter_date',
        'award_letter_path',
        'committee_name',
        'committee_members',
        'status',
    ];

    protected $casts = [
        'decision_date' => 'date',
        'award_amount' => 'decimal:3',
        'preliminary_announcement_date' => 'date',
        'standstill_period_days' => 'integer',
        'standstill_end_date' => 'date',
        'objections_received' => 'boolean',
        'objections_details' => 'array',
        'final_decision_date' => 'date',
        'award_letter_date' => 'date',
        'committee_members' => 'array',
    ];

    // أنواع القرار
    public const DECISION_TYPES = [
        'award_to_winner' => 'إحالة للفائز',
        'award_to_second' => 'إحالة للثاني',
        'cancel_tender' => 'إلغاء المناقصة',
        'rebid' => 'إعادة الطرح',
        'negotiate' => 'التفاوض',
    ];

    // حالات القرار
    public const STATUSES = [
        'preliminary' => 'مبدئي',
        'final' => 'نهائي',
        'cancelled' => 'ملغي',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(TenderCompetitor::class, 'winner_competitor_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function getDecisionTypeNameAttribute(): string
    {
        return self::DECISION_TYPES[$this->decision_type] ?? $this->decision_type;
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * هل انتهت فترة التوقف (الاعتراض)
     */
    public function getIsStandstillOverAttribute(): bool
    {
        return $this->standstill_end_date && $this->standstill_end_date->isPast();
    }

    /**
     * الأيام المتبقية لفترة التوقف
     */
    public function getStandstillDaysRemainingAttribute(): ?int
    {
        if (!$this->standstill_end_date) {
            return null;
        }
        return now()->diffInDays($this->standstill_end_date, false);
    }
}
