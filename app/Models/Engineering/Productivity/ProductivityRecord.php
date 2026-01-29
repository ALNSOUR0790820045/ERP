<?php

namespace App\Models\Engineering\Productivity;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductivityRecord extends Model
{
    protected $fillable = [
        'project_id',
        'wbs_id',
        'record_date',
        'activity_code',
        'activity_description',
        'unit',
        'planned_quantity',
        'actual_quantity',
        'planned_manhours',
        'actual_manhours',
        'planned_productivity',
        'actual_productivity',
        'productivity_factor',
        'crew_size',
        'conditions',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'record_date' => 'date',
        'planned_quantity' => 'decimal:4',
        'actual_quantity' => 'decimal:4',
        'planned_manhours' => 'decimal:2',
        'actual_manhours' => 'decimal:2',
        'planned_productivity' => 'decimal:4',
        'actual_productivity' => 'decimal:4',
        'productivity_factor' => 'decimal:4',
        'crew_size' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function wbs(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ProjectWbs::class, 'wbs_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected static function booted()
    {
        static::saving(function ($record) {
            // Calculate productivity values
            if ($record->planned_manhours > 0) {
                $record->planned_productivity = $record->planned_quantity / $record->planned_manhours;
            }
            if ($record->actual_manhours > 0) {
                $record->actual_productivity = $record->actual_quantity / $record->actual_manhours;
            }
            if ($record->planned_productivity > 0) {
                $record->productivity_factor = $record->actual_productivity / $record->planned_productivity;
            }
        });
    }

    public function getEfficiencyPercentage(): float
    {
        return $this->productivity_factor * 100;
    }

    public function getPerformanceStatus(): string
    {
        if ($this->productivity_factor >= 1) return 'above_target';
        if ($this->productivity_factor >= 0.9) return 'on_target';
        if ($this->productivity_factor >= 0.75) return 'below_target';
        return 'critical';
    }

    public function scopeByProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeByActivity($query, string $activityCode)
    {
        return $query->where('activity_code', $activityCode);
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('record_date', [$startDate, $endDate]);
    }

    public function scopeBelowTarget($query)
    {
        return $query->where('productivity_factor', '<', 0.9);
    }
}
