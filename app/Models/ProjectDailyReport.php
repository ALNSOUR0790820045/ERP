<?php

namespace App\Models;

use App\Enums\WeatherCondition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectDailyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'report_number',
        'report_date',
        'weather',
        'temperature_min',
        'temperature_max',
        'working_hours',
        'shift',
        'is_working_day',
        'non_working_reason',
        'summary',
        'problems',
        'solutions',
        'tomorrow_plan',
        'status',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'report_date' => 'date',
        'weather' => WeatherCondition::class,
        'working_hours' => 'decimal:2',
        'is_working_day' => 'boolean',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function labor(): HasMany
    {
        return $this->hasMany(ProjectDailyLabor::class, 'daily_report_id');
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(ProjectDailyEquipment::class, 'daily_report_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProjectDailyMaterial::class, 'daily_report_id');
    }

    public function works(): HasMany
    {
        return $this->hasMany(ProjectDailyWork::class, 'daily_report_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProjectDailyEvent::class, 'daily_report_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProjectDailyPhoto::class, 'daily_report_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalLaborCountAttribute(): int
    {
        return $this->labor->sum('count');
    }

    public function getTotalLaborHoursAttribute(): float
    {
        return $this->labor->sum('hours');
    }
}
