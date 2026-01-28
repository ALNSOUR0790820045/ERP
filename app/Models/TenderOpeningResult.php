<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderOpeningResult extends Model
{
    protected $fillable = [
        'tender_id',
        'opening_date',
        'opening_location',
        'our_rank',
        'total_bidders',
        'our_price',
        'lowest_price',
        'highest_price',
        'average_price',
        'winning_price',
        'technical_score',
        'financial_score',
        'total_score',
        'notes',
        'disqualification_reason',
        'is_qualified',
    ];

    protected $casts = [
        'opening_date' => 'datetime',
        'our_price' => 'decimal:3',
        'lowest_price' => 'decimal:3',
        'highest_price' => 'decimal:3',
        'average_price' => 'decimal:3',
        'winning_price' => 'decimal:3',
        'technical_score' => 'decimal:2',
        'financial_score' => 'decimal:2',
        'total_score' => 'decimal:2',
        'is_qualified' => 'boolean',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function getPositionAnalysis(): array
    {
        $percentFromLowest = $this->lowest_price > 0 
            ? (($this->our_price - $this->lowest_price) / $this->lowest_price) * 100 
            : 0;

        $percentFromAverage = $this->average_price > 0 
            ? (($this->our_price - $this->average_price) / $this->average_price) * 100 
            : 0;

        return [
            'rank' => $this->our_rank,
            'total_bidders' => $this->total_bidders,
            'percent_from_lowest' => round($percentFromLowest, 2),
            'percent_from_average' => round($percentFromAverage, 2),
            'is_lowest' => $this->our_rank === 1,
            'competitive_position' => $this->getCompetitivePosition(),
        ];
    }

    private function getCompetitivePosition(): string
    {
        if ($this->our_rank === 1) return 'الأدنى سعراً';
        if ($this->our_rank <= 3) return 'تنافسي';
        if ($this->our_rank <= $this->total_bidders / 2) return 'متوسط';
        return 'مرتفع';
    }
}
