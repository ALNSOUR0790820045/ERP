<?php

namespace App\Models\Tenders;

use App\Models\Tender;
use App\Models\TenderAwardDecision;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تتبع قرار الإحالة
 * Tender Award Tracking
 */
class TenderAwardTracking extends Model
{
    protected $table = 'tender_award_tracking';

    protected $fillable = [
        'tender_id',
        'award_decision_id',
        'tracking_status',
        'status_date',
        'status_notes',
        'reference_document',
        'document_path',
        // الإحالة المبدئية
        'preliminary_award_date',
        'preliminary_award_amount',
        // فترة الاعتراض
        'objection_period_start',
        'objection_period_end',
        'objections_filed',
        'objection_details',
        // الإحالة النهائية
        'final_award_date',
        'final_award_amount',
        'award_letter_number',
        'award_letter_path',
        'updated_by',
    ];

    protected $casts = [
        'status_date' => 'date',
        'preliminary_award_date' => 'date',
        'preliminary_award_amount' => 'decimal:3',
        'objection_period_start' => 'date',
        'objection_period_end' => 'date',
        'objections_filed' => 'boolean',
        'final_award_date' => 'date',
        'final_award_amount' => 'decimal:3',
    ];

    // حالات التتبع
    public const TRACKING_STATUSES = [
        'awaiting_technical_evaluation' => 'انتظار التقييم الفني',
        'technical_evaluation_complete' => 'اكتمل التقييم الفني',
        'awaiting_financial_opening' => 'انتظار فتح المالي',
        'financial_opening_complete' => 'اكتمل فتح المالي',
        'awaiting_committee_decision' => 'انتظار قرار اللجنة',
        'preliminary_award' => 'إحالة مبدئية',
        'objection_period' => 'فترة الاعتراض',
        'final_award' => 'إحالة نهائية',
        'award_cancelled' => 'إلغاء الإحالة',
    ];

    // العلاقات
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function awardDecision(): BelongsTo
    {
        return $this->belongsTo(TenderAwardDecision::class, 'award_decision_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('tracking_status', $status);
    }

    public function scopeAwaitingDecision($query)
    {
        return $query->whereIn('tracking_status', [
            'awaiting_technical_evaluation',
            'awaiting_financial_opening',
            'awaiting_committee_decision',
        ]);
    }

    public function scopeInObjectionPeriod($query)
    {
        return $query->where('tracking_status', 'objection_period')
            ->where('objection_period_end', '>=', now());
    }

    public function scopeFinallyAwarded($query)
    {
        return $query->where('tracking_status', 'final_award');
    }

    // Methods
    public function updateStatus(string $status, User $user, ?string $notes = null): void
    {
        $this->update([
            'tracking_status' => $status,
            'status_date' => now(),
            'status_notes' => $notes,
            'updated_by' => $user->id,
        ]);
    }

    public function startObjectionPeriod(int $days = 14): void
    {
        $this->update([
            'tracking_status' => 'objection_period',
            'objection_period_start' => now(),
            'objection_period_end' => now()->addDays($days),
        ]);
    }

    public function recordObjection(string $details): void
    {
        $this->update([
            'objections_filed' => true,
            'objection_details' => $details,
        ]);
    }

    public function finalizeAward(float $amount, string $letterNumber, ?string $letterPath = null): void
    {
        $this->update([
            'tracking_status' => 'final_award',
            'final_award_date' => now(),
            'final_award_amount' => $amount,
            'award_letter_number' => $letterNumber,
            'award_letter_path' => $letterPath,
        ]);

        // تحديث العطاء
        $this->tender->update([
            'result' => 'won',
            'award_date' => now(),
            'winning_price' => $amount,
        ]);
    }

    public function cancelAward(?string $reason = null): void
    {
        $this->update([
            'tracking_status' => 'award_cancelled',
            'status_date' => now(),
            'status_notes' => $reason,
        ]);
    }

    public function isInObjectionPeriod(): bool
    {
        return $this->tracking_status === 'objection_period'
            && $this->objection_period_end >= now();
    }

    public function getDaysRemainingInObjectionPeriod(): ?int
    {
        if (!$this->isInObjectionPeriod()) {
            return null;
        }
        return now()->diffInDays($this->objection_period_end);
    }

    // Accessors
    public function getTrackingStatusLabelAttribute(): string
    {
        return self::TRACKING_STATUSES[$this->tracking_status] ?? $this->tracking_status;
    }

    public function getIsFinalAttribute(): bool
    {
        return in_array($this->tracking_status, ['final_award', 'award_cancelled']);
    }
}
