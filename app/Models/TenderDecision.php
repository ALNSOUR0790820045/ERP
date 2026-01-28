<?php

namespace App\Models;

use App\Enums\TenderResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderDecision extends Model
{
    protected $fillable = [
        'tender_id',
        'decision_type',
        'decision_date',
        'decision_number',
        'decided_by',
        'result',
        'reason',
        'conditions',
        'follow_up_actions',
        'follow_up_deadline',
        'is_final',
        'notes',
        'attachments',
    ];

    protected $casts = [
        'decision_date' => 'date',
        'result' => TenderResult::class,
        'conditions' => 'array',
        'follow_up_actions' => 'array',
        'follow_up_deadline' => 'date',
        'is_final' => 'boolean',
        'attachments' => 'array',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function getDecisionTypeLabel(): string
    {
        return match($this->decision_type) {
            'go_no_go' => 'قرار المشاركة',
            'pricing_approval' => 'اعتماد التسعير',
            'submission_approval' => 'اعتماد التقديم',
            'negotiation' => 'تفاوض',
            'contract_award' => 'ترسية',
            'project_start' => 'بدء المشروع',
            default => $this->decision_type,
        };
    }

    public function getFollowUpStatus(): string
    {
        if (!$this->follow_up_deadline) return 'لا يوجد';
        
        if ($this->follow_up_deadline->isPast()) {
            return 'متأخر';
        }
        
        if ($this->follow_up_deadline->isToday()) {
            return 'اليوم';
        }
        
        $daysRemaining = now()->diffInDays($this->follow_up_deadline);
        if ($daysRemaining <= 3) {
            return "قريب ({$daysRemaining} أيام)";
        }
        
        return 'في الموعد';
    }

    protected static function booted(): void
    {
        static::saved(function (TenderDecision $decision) {
            if ($decision->is_final && $decision->result) {
                TenderActivity::log(
                    $decision->tender,
                    'decision_made',
                    "تم اتخاذ قرار: {$decision->getDecisionTypeLabel()}",
                    null,
                    null,
                    ['decision_id' => $decision->id, 'result' => $decision->result->value]
                );
            }
        });
    }
}
