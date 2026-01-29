<?php

namespace App\Models\Engineering\Productivity;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherLog extends Model
{
    protected $fillable = [
        'project_id',
        'log_date',
        'log_time',
        'temperature_high',
        'temperature_low',
        'conditions',
        'precipitation_mm',
        'wind_speed_kmh',
        'wind_direction',
        'humidity_percent',
        'work_impacted',
        'lost_hours',
        'impact_description',
        'recorded_by',
    ];

    protected $casts = [
        'log_date' => 'date',
        'log_time' => 'datetime',
        'temperature_high' => 'decimal:2',
        'temperature_low' => 'decimal:2',
        'precipitation_mm' => 'decimal:2',
        'wind_speed_kmh' => 'decimal:2',
        'humidity_percent' => 'decimal:2',
        'work_impacted' => 'boolean',
        'lost_hours' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getAverageTemperature(): ?float
    {
        if ($this->temperature_high === null || $this->temperature_low === null) {
            return null;
        }
        return ($this->temperature_high + $this->temperature_low) / 2;
    }

    public function isExtremeWeather(): bool
    {
        return $this->temperature_high > 40 
            || $this->temperature_low < 0 
            || $this->wind_speed_kmh > 50 
            || $this->precipitation_mm > 20;
    }

    public function scopeByProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeWithImpact($query)
    {
        return $query->where('work_impacted', true);
    }

    public function scopeInMonth($query, int $year, int $month)
    {
        return $query->whereYear('log_date', $year)
            ->whereMonth('log_date', $month);
    }

    public static function totalLostHours(int $projectId, $startDate = null, $endDate = null): float
    {
        $query = self::where('project_id', $projectId)->where('work_impacted', true);
        
        if ($startDate && $endDate) {
            $query->whereBetween('log_date', [$startDate, $endDate]);
        }
        
        return $query->sum('lost_hours');
    }
}
