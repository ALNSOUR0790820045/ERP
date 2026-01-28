<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    use SoftDeletes;

    protected $table = 'equipment';

    protected $fillable = [
        'category_id', 'company_id', 'code', 'name_ar', 'name_en', 'description',
        'brand', 'model', 'serial_number', 'year_manufactured', 'ownership_type',
        'purchase_date', 'purchase_price', 'current_value', 'depreciation_rate',
        'hourly_rate', 'daily_rate', 'monthly_rate', 'fuel_consumption',
        'fuel_type', 'capacity', 'capacity_unit', 'status', 'current_project_id',
        'current_operator_id', 'odometer', 'hour_meter', 'is_active',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:3',
        'current_value' => 'decimal:3',
        'depreciation_rate' => 'decimal:2',
        'hourly_rate' => 'decimal:3',
        'daily_rate' => 'decimal:3',
        'monthly_rate' => 'decimal:3',
        'fuel_consumption' => 'decimal:2',
        'capacity' => 'decimal:2',
        'odometer' => 'decimal:2',
        'hour_meter' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(EquipmentCategory::class, 'category_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function currentProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'current_project_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EquipmentAssignment::class);
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(EquipmentUsageLog::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(EquipmentMaintenance::class);
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(EquipmentFuelLog::class);
    }
}
