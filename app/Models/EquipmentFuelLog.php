<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentFuelLog extends Model
{
    protected $fillable = [
        'equipment_id', 'project_id', 'fuel_date', 'fuel_type',
        'quantity', 'unit_price', 'total_cost', 'odometer_reading',
        'hour_meter_reading', 'fuel_station', 'receipt_number',
    ];

    protected $casts = [
        'fuel_date' => 'date',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:3',
        'total_cost' => 'decimal:3',
        'odometer_reading' => 'decimal:2',
        'hour_meter_reading' => 'decimal:2',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
