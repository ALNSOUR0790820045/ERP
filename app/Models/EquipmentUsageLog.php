<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentUsageLog extends Model
{
    protected $fillable = [
        'equipment_id', 'project_id', 'daily_report_id', 'usage_date',
        'working_hours', 'idle_hours', 'fuel_consumed', 'odometer_start',
        'odometer_end', 'hour_meter_start', 'hour_meter_end',
        'operator_name', 'work_description', 'notes',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'working_hours' => 'decimal:2',
        'idle_hours' => 'decimal:2',
        'fuel_consumed' => 'decimal:2',
        'odometer_start' => 'decimal:2',
        'odometer_end' => 'decimal:2',
        'hour_meter_start' => 'decimal:2',
        'hour_meter_end' => 'decimal:2',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(ProjectDailyReport::class, 'daily_report_id');
    }
}
