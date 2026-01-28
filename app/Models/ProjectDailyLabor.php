<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDailyLabor extends Model
{
    use HasFactory;

    protected $table = 'project_daily_labor';

    protected $fillable = [
        'daily_report_id',
        'labor_type',
        'trade',
        'count',
        'hours',
        'overtime_hours',
        'wbs_id',
        'activity_description',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
    ];

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(ProjectDailyReport::class, 'daily_report_id');
    }

    public function wbs(): BelongsTo
    {
        return $this->belongsTo(ProjectWbs::class, 'wbs_id');
    }

    public function getTotalHoursAttribute(): float
    {
        return $this->hours + $this->overtime_hours;
    }

    public function getTotalManHoursAttribute(): float
    {
        return $this->count * $this->total_hours;
    }
}
