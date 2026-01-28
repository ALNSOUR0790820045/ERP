<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseLocation extends Model
{
    protected $fillable = [
        'warehouse_id',
        'location_code',
        'zone',
        'aisle',
        'rack',
        'shelf',
        'bin',
        'capacity',
        'current_usage',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'decimal:3',
        'current_usage' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(LocationInventory::class);
    }

    public function getFullLocationAttribute(): string
    {
        $parts = array_filter([
            $this->zone,
            $this->aisle,
            $this->rack,
            $this->shelf,
            $this->bin,
        ]);
        return implode('-', $parts);
    }

    public function getAvailableCapacityAttribute(): float
    {
        if (!$this->capacity) {
            return 0;
        }
        return $this->capacity - $this->current_usage;
    }

    public function getUsagePercentageAttribute(): float
    {
        if (!$this->capacity || $this->capacity == 0) {
            return 0;
        }
        return ($this->current_usage / $this->capacity) * 100;
    }
}
