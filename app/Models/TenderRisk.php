<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderRisk extends Model
{
    protected $fillable = [
        'tender_id',
        'risk_name',
        'description',
        'probability',
        'impact',
        'mitigation',
        'contingency_amount',
    ];

    protected $casts = [
        'contingency_amount' => 'decimal:3',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function getRiskScoreAttribute(): int
    {
        $probScore = match($this->probability) {
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'very_high' => 4,
            default => 2,
        };
        
        $impactScore = match($this->impact) {
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'very_high' => 4,
            default => 2,
        };
        
        return $probScore * $impactScore;
    }

    public function getRiskLevelAttribute(): string
    {
        $score = $this->risk_score;
        return match(true) {
            $score <= 2 => 'منخفض',
            $score <= 6 => 'متوسط',
            $score <= 12 => 'عالي',
            default => 'حرج',
        };
    }
}
