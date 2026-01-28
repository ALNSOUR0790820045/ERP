<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiValue extends Model
{
    protected $fillable = [
        'kpi_id',
        'project_id',
        'period_date',
        'year',
        'month',
        'week',
        'value',
        'target_value',
        'previous_value',
        'status',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'period_date' => 'date',
        'value' => 'decimal:4',
        'target_value' => 'decimal:4',
        'previous_value' => 'decimal:4',
    ];

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getVarianceAttribute(): ?float
    {
        if (!$this->target_value) return null;
        return $this->value - $this->target_value;
    }

    public function getVariancePercentAttribute(): ?float
    {
        if (!$this->target_value || $this->target_value == 0) return null;
        return round((($this->value - $this->target_value) / $this->target_value) * 100, 2);
    }

    public function getChangeFromPreviousAttribute(): ?float
    {
        if (!$this->previous_value) return null;
        return $this->value - $this->previous_value;
    }

    protected static function booted(): void
    {
        static::creating(function ($kpiValue) {
            // تحديد الحالة بناءً على العتبات
            $kpi = $kpiValue->kpi;
            if ($kpi) {
                $value = $kpiValue->value;
                $isHigherBetter = $kpi->comparison_type === 'higher_better';
                
                if ($kpi->critical_threshold) {
                    if (($isHigherBetter && $value <= $kpi->critical_threshold) ||
                        (!$isHigherBetter && $value >= $kpi->critical_threshold)) {
                        $kpiValue->status = 'critical';
                    } elseif ($kpi->warning_threshold && 
                             (($isHigherBetter && $value <= $kpi->warning_threshold) ||
                              (!$isHigherBetter && $value >= $kpi->warning_threshold))) {
                        $kpiValue->status = 'warning';
                    } else {
                        $kpiValue->status = 'on_track';
                    }
                }
            }
        });
    }
}
