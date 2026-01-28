<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerEvaluation extends Model
{
    protected $fillable = [
        'customer_id',
        'evaluation_date',
        'year',
        'quarter',
        'payment_score',
        'communication_score',
        'repeat_business_score',
        'overall_score',
        'strengths',
        'weaknesses',
        'recommendations',
        'evaluated_by',
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'payment_score' => 'decimal:2',
        'communication_score' => 'decimal:2',
        'repeat_business_score' => 'decimal:2',
        'overall_score' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function evaluatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    protected static function booted(): void
    {
        static::saving(function ($evaluation) {
            // حساب التقييم الإجمالي
            $scores = array_filter([
                $evaluation->payment_score,
                $evaluation->communication_score,
                $evaluation->repeat_business_score,
            ]);
            
            if (count($scores) > 0) {
                $evaluation->overall_score = array_sum($scores) / count($scores);
            }
        });
    }

    public function getGradeAttribute(): string
    {
        if ($this->overall_score >= 90) return 'A+';
        if ($this->overall_score >= 80) return 'A';
        if ($this->overall_score >= 70) return 'B';
        if ($this->overall_score >= 60) return 'C';
        if ($this->overall_score >= 50) return 'D';
        return 'F';
    }
}
