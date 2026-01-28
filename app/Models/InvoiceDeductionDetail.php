<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceDeductionDetail extends Model
{
    protected $fillable = [
        'invoice_id',
        'deduction_type',
        'description',
        'rate',
        'base_amount',
        'amount',
        'previous_amount',
        'cumulative_amount',
        'limit_amount',
        'notes',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'base_amount' => 'decimal:3',
        'amount' => 'decimal:3',
        'previous_amount' => 'decimal:3',
        'cumulative_amount' => 'decimal:3',
        'limit_amount' => 'decimal:3',
    ];

    protected static function booted(): void
    {
        static::saving(function ($model) {
            $model->cumulative_amount = $model->previous_amount + $model->amount;
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getDeductionTypeNameAttribute(): string
    {
        return match($this->deduction_type) {
            'advance_recovery' => 'استرداد الدفعة المقدمة',
            'retention' => 'المحتجزات',
            'income_tax' => 'ضريبة الدخل',
            'sales_tax' => 'ضريبة المبيعات',
            'contractor_union' => 'نقابة المقاولين',
            'liquidated_damages' => 'غرامات التأخير',
            'backcharges' => 'خصومات المقاول',
            'other' => 'أخرى',
            default => $this->deduction_type,
        };
    }

    public function getIsAtLimitAttribute(): bool
    {
        return $this->limit_amount && $this->cumulative_amount >= $this->limit_amount;
    }
}
