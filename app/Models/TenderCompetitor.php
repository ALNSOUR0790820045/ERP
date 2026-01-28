<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderCompetitor extends Model
{
    protected $fillable = [
        'tender_opening_result_id',
        'rank',
        'company_name',
        'price',
        'price_per_unit',
        'technical_score',
        'financial_score',
        'total_score',
        'is_qualified',
        'disqualification_reason',
        'notes',
    ];

    protected $casts = [
        'price' => 'decimal:3',
        'price_per_unit' => 'decimal:3',
        'technical_score' => 'decimal:2',
        'financial_score' => 'decimal:2',
        'total_score' => 'decimal:2',
        'is_qualified' => 'boolean',
    ];

    public function openingResult(): BelongsTo
    {
        return $this->belongsTo(TenderOpeningResult::class, 'tender_opening_result_id');
    }

    public function getPriceComparison(): array
    {
        $tender = $this->openingResult->tender;
        $ourPrice = $this->openingResult->our_price;

        $priceDiff = $this->price - $ourPrice;
        $percentDiff = $ourPrice > 0 
            ? ($priceDiff / $ourPrice) * 100 
            : 0;

        return [
            'price_difference' => $priceDiff,
            'percent_difference' => round($percentDiff, 2),
            'is_lower' => $this->price < $ourPrice,
            'is_higher' => $this->price > $ourPrice,
        ];
    }

    public function getStatusLabel(): string
    {
        if (!$this->is_qualified) return 'غير مؤهل';
        if ($this->rank === 1) return 'الفائز';
        return "المركز {$this->rank}";
    }
}
