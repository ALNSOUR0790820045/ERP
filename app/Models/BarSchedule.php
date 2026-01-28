<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarSchedule extends Model
{
    protected $fillable = [
        'steel_order_id',
        'mark',
        'diameter',
        'shape',
        'length',
        'count',
        'unit_weight',
        'total_weight',
        'location',
        'notes',
    ];

    protected $casts = [
        'length' => 'decimal:3',
        'unit_weight' => 'decimal:4',
        'total_weight' => 'decimal:3',
    ];

    // Unit weights for rebar (kg/m)
    public const UNIT_WEIGHTS = [
        8 => 0.395,
        10 => 0.617,
        12 => 0.888,
        14 => 1.208,
        16 => 1.578,
        18 => 1.998,
        20 => 2.466,
        22 => 2.984,
        25 => 3.853,
        28 => 4.834,
        32 => 6.313,
    ];

    public function steelOrder(): BelongsTo
    {
        return $this->belongsTo(SteelFabricationOrder::class, 'steel_order_id');
    }

    protected static function booted(): void
    {
        static::saving(function ($bar) {
            // Set unit weight based on diameter
            if (!$bar->unit_weight && isset(self::UNIT_WEIGHTS[$bar->diameter])) {
                $bar->unit_weight = self::UNIT_WEIGHTS[$bar->diameter];
            }
            
            // Calculate total weight
            if ($bar->length && $bar->count && $bar->unit_weight) {
                $bar->total_weight = $bar->length * $bar->count * $bar->unit_weight;
            }
        });

        static::saved(function ($bar) {
            $bar->steelOrder?->calculateTotalWeight();
        });

        static::deleted(function ($bar) {
            $bar->steelOrder?->calculateTotalWeight();
        });
    }
}
