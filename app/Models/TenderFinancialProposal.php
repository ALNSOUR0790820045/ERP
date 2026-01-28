<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * العرض المالي
 * Tender Financial Proposal
 */
class TenderFinancialProposal extends Model
{
    protected $fillable = [
        'tender_id',
        'priced_boq_path',
        'price_summary_path',
        'price_breakdown_path',
        'direct_cost',
        'indirect_cost',
        'contingency',
        'profit',
        'total_before_tax',
        'tax_amount',
        'grand_total',
        'currency_id',
        'package_prices',
        'status',
    ];

    protected $casts = [
        'direct_cost' => 'decimal:3',
        'indirect_cost' => 'decimal:3',
        'contingency' => 'decimal:3',
        'profit' => 'decimal:3',
        'total_before_tax' => 'decimal:3',
        'tax_amount' => 'decimal:3',
        'grand_total' => 'decimal:3',
        'package_prices' => 'array',
    ];

    public const STATUSES = [
        'draft' => 'مسودة',
        'ready' => 'جاهز',
        'submitted' => 'مقدم',
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
     * حساب المجاميع
     */
    public function calculateTotals(): void
    {
        $this->total_before_tax = $this->direct_cost 
            + $this->indirect_cost 
            + $this->contingency 
            + $this->profit;
        
        $this->grand_total = $this->total_before_tax + $this->tax_amount;
        $this->save();
    }

    /**
     * نسبة الربح
     */
    public function getProfitPercentageAttribute(): ?float
    {
        if (!$this->direct_cost || $this->direct_cost == 0) {
            return null;
        }
        return ($this->profit / $this->direct_cost) * 100;
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
