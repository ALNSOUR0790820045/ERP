<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceAdjustmentReading extends Model
{
    protected $fillable = [
        'price_adjustment_index_id',
        'reading_date',
        'value',
        'ratio',
        'reference',
    ];

    protected $casts = [
        'reading_date' => 'date',
        'value' => 'decimal:4',
        'ratio' => 'decimal:6',
    ];

    protected static function booted(): void
    {
        static::saving(function ($model) {
            if ($model->priceAdjustmentIndex && $model->priceAdjustmentIndex->base_value > 0) {
                $model->ratio = $model->value / $model->priceAdjustmentIndex->base_value;
            }
        });
    }

    public function priceAdjustmentIndex(): BelongsTo
    {
        return $this->belongsTo(PriceAdjustmentIndex::class);
    }
}
