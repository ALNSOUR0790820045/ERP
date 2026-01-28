<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoqLabor extends Model
{
    protected $table = 'boq_labor';

    protected $fillable = [
        'boq_item_id',
        'labor_type',
        'description',
        'rate_unit',
        'productivity',
        'hours_per_unit',
        'hourly_rate',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'productivity' => 'decimal:6',
        'hours_per_unit' => 'decimal:6',
        'hourly_rate' => 'decimal:3',
        'total_cost' => 'decimal:3',
    ];

    public function boqItem(): BelongsTo
    {
        return $this->belongsTo(BoqItem::class);
    }

    public function getRateUnitLabel(): string
    {
        return match($this->rate_unit) {
            'hour' => 'ساعة',
            'day' => 'يوم',
            'unit' => 'وحدة',
            default => $this->rate_unit,
        };
    }

    protected static function booted(): void
    {
        static::saving(function (BoqLabor $labor) {
            $boqQuantity = $labor->boqItem->quantity ?? 0;
            $totalHours = $boqQuantity * $labor->hours_per_unit;
            $labor->total_cost = $totalHours * $labor->hourly_rate;
        });

        static::saved(function (BoqLabor $labor) {
            $labor->boqItem->calculateCosts();
        });

        static::deleted(function (BoqLabor $labor) {
            $labor->boqItem->calculateCosts();
        });
    }
}
