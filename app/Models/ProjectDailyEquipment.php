<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDailyEquipment extends Model
{
    use HasFactory;

    protected $table = 'project_daily_equipment';

    protected $fillable = [
        'daily_report_id',
        'equipment_name',
        'equipment_code',
        'status',
        'working_hours',
        'idle_hours',
        'fuel_consumption',
        'wbs_id',
        'notes',
    ];

    protected $casts = [
        'working_hours' => 'decimal:2',
        'idle_hours' => 'decimal:2',
        'fuel_consumption' => 'decimal:2',
    ];

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(ProjectDailyReport::class, 'daily_report_id');
    }

    public function wbs(): BelongsTo
    {
        return $this->belongsTo(ProjectWbs::class, 'wbs_id');
    }

    public function getUtilizationRateAttribute(): float
    {
        $total = $this->working_hours + $this->idle_hours;
        if ($total <= 0) {
            return 0;
        }
        return round(($this->working_hours / $total) * 100, 2);
    }
}
