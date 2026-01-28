<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * كتاب عرض المناقصة
 * Tender Bid Letter - نموذج 1 من نماذج العرض
 */
class TenderBidLetter extends Model
{
    protected $fillable = [
        'tender_id',
        'letter_date',
        'letter_number',
        'total_amount',
        'amount_in_words_ar',
        'amount_in_words_en',
        'currency_id',
        'has_discount',
        'discount_percentage',
        'discount_amount',
        'discount_conditions',
        'has_alternatives',
        'alternatives_description',
        'validity_days',
        'completion_period_days',
        'expected_start_date',
        'accepts_general_conditions',
        'accepts_special_conditions',
        'examined_documents',
        'visited_site',
        'no_conflict_of_interest',
        'not_blacklisted',
        'authorized_signatory',
        'signatory_position',
        'signature_path',
        'stamp_path',
    ];

    protected $casts = [
        'letter_date' => 'date',
        'total_amount' => 'decimal:3',
        'has_discount' => 'boolean',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:3',
        'has_alternatives' => 'boolean',
        'validity_days' => 'integer',
        'completion_period_days' => 'integer',
        'expected_start_date' => 'date',
        'accepts_general_conditions' => 'boolean',
        'accepts_special_conditions' => 'boolean',
        'examined_documents' => 'boolean',
        'visited_site' => 'boolean',
        'no_conflict_of_interest' => 'boolean',
        'not_blacklisted' => 'boolean',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * الحصول على السعر بعد الخصم
     */
    public function getNetAmountAttribute(): float
    {
        if ($this->has_discount && $this->discount_amount) {
            return $this->total_amount - $this->discount_amount;
        }
        if ($this->has_discount && $this->discount_percentage) {
            return $this->total_amount * (1 - $this->discount_percentage / 100);
        }
        return $this->total_amount;
    }
}
