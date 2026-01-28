<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoqEquipment extends Model
{
    protected $table = 'boq_equipment';

    protected $fillable = [
        'boq_item_id',
        'equipment_type',
        'description',
        'rate_unit',
        'productivity',
        'hours_per_unit',
        'hourly_rate',
        'ownership',
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
            'month' => 'شهر',
            default => $this->rate_unit,
        };
    }

    public function getOwnershipLabel(): string
    {
        return match($this->ownership) {
            'owned' => 'ملك',
            'rented' => 'إيجار',
            default => $this->ownership,
        };
    }

    protected static function booted(): void
    {
        static::saving(function (BoqEquipment $equipment) {
            $boqQuantity = $equipment->boqItem->quantity ?? 0;
            $totalHours = $boqQuantity * $equipment->hours_per_unit;
            $equipment->total_cost = $totalHours * $equipment->hourly_rate;
        });

        static::saved(function (BoqEquipment $equipment) {
            $equipment->boqItem->calculateCosts();
        });

        static::deleted(function (BoqEquipment $equipment) {
            $equipment->boqItem->calculateCosts();
        });
    }
}
