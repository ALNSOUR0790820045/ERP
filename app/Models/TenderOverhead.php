<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderOverhead extends Model
{
    protected $fillable = [
        'tender_id',
        'category',
        'description',
        'amount',
        'percentage',
        'calculation_base',
        'calculated_amount',
        'is_fixed',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'percentage' => 'decimal:4',
        'calculated_amount' => 'decimal:3',
        'is_fixed' => 'boolean',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function getCategoryLabel(): string
    {
        return match($this->category) {
            'general_conditions' => 'اشتراطات عامة',
            'site_overhead' => 'مصاريف موقع',
            'head_office' => 'مصاريف مقر رئيسي',
            'profit' => 'ربح',
            'contingency' => 'طوارئ',
            'insurance' => 'تأمين',
            'bond_cost' => 'تكلفة ضمانات',
            'tax' => 'ضرائب',
            'other' => 'أخرى',
            default => $this->category,
        };
    }

    public function getCalculationBaseLabel(): string
    {
        return match($this->calculation_base) {
            'direct_cost' => 'التكلفة المباشرة',
            'total_cost' => 'إجمالي التكلفة',
            'tender_price' => 'قيمة العطاء',
            default => $this->calculation_base,
        };
    }

    protected static function booted(): void
    {
        static::saving(function (TenderOverhead $overhead) {
            if ($overhead->is_fixed) {
                $overhead->calculated_amount = $overhead->amount;
            } else {
                $baseAmount = match($overhead->calculation_base) {
                    'direct_cost' => $overhead->tender->direct_cost ?? 0,
                    'total_cost' => $overhead->tender->total_cost ?? 0,
                    'tender_price' => $overhead->tender->tender_price ?? 0,
                    default => 0,
                };
                $overhead->calculated_amount = $baseAmount * ($overhead->percentage / 100);
            }
        });
    }
}
