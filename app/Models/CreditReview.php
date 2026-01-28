<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditReview extends Model
{
    protected $fillable = [
        'customer_credit_profile_id',
        'review_date',
        'previous_rating',
        'new_rating',
        'previous_score',
        'new_score',
        'score_factors',
        'review_notes',
        'reviewed_by',
    ];

    protected $casts = [
        'review_date' => 'date',
        'previous_score' => 'integer',
        'new_score' => 'integer',
        'score_factors' => 'array',
    ];

    public function creditProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerCreditProfile::class, 'customer_credit_profile_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getRatingChangeAttribute(): string
    {
        if ($this->new_rating < $this->previous_rating) return 'upgrade';
        if ($this->new_rating > $this->previous_rating) return 'downgrade';
        return 'unchanged';
    }
}
