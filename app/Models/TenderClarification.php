<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * الاستيضاحات
 * Tender Clarifications - أسئلة وأجوبة العطاء
 */
class TenderClarification extends Model
{
    protected $fillable = [
        'tender_id',
        'question_number',
        'question_date',
        'question_ar',
        'question_en',
        'question_source',
        'answer_ar',
        'answer_en',
        'answer_date',
        'answer_reference',
        'answer_document_path',
        'affects_boq',
        'affects_price',
        'affects_schedule',
        'impact_notes',
        'status',
    ];

    protected $casts = [
        'question_date' => 'date',
        'answer_date' => 'date',
        'affects_boq' => 'boolean',
        'affects_price' => 'boolean',
        'affects_schedule' => 'boolean',
    ];

    // مصادر الأسئلة
    public const SOURCES = [
        'our_company' => 'شركتنا',
        'other_bidder' => 'مناقص آخر',
        'procuring_entity' => 'الجهة المشترية',
    ];

    // حالات السؤال
    public const STATUSES = [
        'pending' => 'بانتظار الرد',
        'answered' => 'تم الرد',
        'no_response' => 'لم يرد عليه',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function getSourceNameAttribute(): string
    {
        return self::SOURCES[$this->question_source] ?? $this->question_source;
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * هل له تأثير على العطاء
     */
    public function getHasImpactAttribute(): bool
    {
        return $this->affects_boq || $this->affects_price || $this->affects_schedule;
    }
}
