<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تفاصيل قياس EVM حسب النشاط
 * EVM Measurement Details by Activity
 */
class EvmMeasurementDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'measurement_id',
        'activity_id',
        'activity_code',
        'activity_name',
        'planned_value',
        'earned_value',
        'actual_cost',
        'physical_progress',
        'schedule_variance',
        'cost_variance',
        'spi',
        'cpi',
        'notes',
    ];

    protected $casts = [
        'planned_value' => 'decimal:3',
        'earned_value' => 'decimal:3',
        'actual_cost' => 'decimal:3',
        'physical_progress' => 'decimal:4',
        'schedule_variance' => 'decimal:3',
        'cost_variance' => 'decimal:3',
        'spi' => 'decimal:4',
        'cpi' => 'decimal:4',
    ];

    // Boot method لحساب المؤشرات
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($model) {
            // SV = EV - PV
            $model->schedule_variance = $model->earned_value - $model->planned_value;
            
            // CV = EV - AC
            $model->cost_variance = $model->earned_value - $model->actual_cost;
            
            // SPI = EV / PV
            $model->spi = $model->planned_value > 0 
                ? $model->earned_value / $model->planned_value 
                : 1;
            
            // CPI = EV / AC
            $model->cpi = $model->actual_cost > 0 
                ? $model->earned_value / $model->actual_cost 
                : 1;
        });
    }

    // العلاقات
    public function measurement(): BelongsTo
    {
        return $this->belongsTo(EvmMeasurement::class, 'measurement_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(BaselineActivity::class, 'activity_id');
    }
}
