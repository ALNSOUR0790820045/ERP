<?php

namespace App\Models\CRM\Engagement;

use App\Models\Customer;
use App\Models\CustomerContact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyResponse extends Model
{
    protected $fillable = [
        'survey_id',
        'customer_id',
        'contact_id',
        'respondent_email',
        'answers',
        'score',
        'nps_score',
        'comments',
        'ip_address',
        'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'score' => 'decimal:2',
        'nps_score' => 'integer',
        'submitted_at' => 'datetime',
    ];

    // العلاقات
    public function survey(): BelongsTo
    {
        return $this->belongsTo(CustomerSurvey::class, 'survey_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CustomerContact::class, 'contact_id');
    }

    // Scopes
    public function scopeForSurvey($query, int $surveyId)
    {
        return $query->where('survey_id', $surveyId);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeWithHighScore($query, float $minScore = 4)
    {
        return $query->where('score', '>=', $minScore);
    }

    public function scopePromoters($query)
    {
        return $query->where('nps_score', '>=', 9);
    }

    public function scopeDetractors($query)
    {
        return $query->where('nps_score', '<=', 6);
    }

    public function scopePassives($query)
    {
        return $query->whereBetween('nps_score', [7, 8]);
    }

    // Methods
    public function calculateScore(): float
    {
        $survey = $this->survey;
        $totalScore = 0;
        $count = 0;
        
        foreach ($survey->questions ?? [] as $index => $question) {
            if ($question['type'] === 'rating' && isset($this->answers[$index])) {
                $totalScore += $this->answers[$index];
                $count++;
            }
        }
        
        return $count > 0 ? $totalScore / $count : 0;
    }

    public function isPromoter(): bool
    {
        return $this->nps_score >= 9;
    }

    public function isDetractor(): bool
    {
        return $this->nps_score !== null && $this->nps_score <= 6;
    }

    public function isPassive(): bool
    {
        return $this->nps_score >= 7 && $this->nps_score <= 8;
    }

    public function getNpsCategory(): string
    {
        if ($this->nps_score === null) return 'unknown';
        if ($this->isPromoter()) return 'promoter';
        if ($this->isDetractor()) return 'detractor';
        return 'passive';
    }
}
