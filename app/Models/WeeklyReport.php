<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'report_number', 'week_number', 'year',
        'period_start', 'period_end', 'weather_conditions',
        'planned_progress', 'actual_progress', 'variance',
        'activities_completed', 'activities_in_progress', 'activities_planned',
        'labor_summary', 'equipment_summary', 'material_summary',
        'safety_incidents', 'quality_issues', 'delays', 'concerns',
        'photos', 'status', 'prepared_by', 'reviewed_by', 'approved_by', 'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'planned_progress' => 'decimal:2',
        'actual_progress' => 'decimal:2',
        'variance' => 'decimal:2',
        'weather_conditions' => 'array',
        'activities_completed' => 'array',
        'activities_in_progress' => 'array',
        'activities_planned' => 'array',
        'labor_summary' => 'array',
        'equipment_summary' => 'array',
        'material_summary' => 'array',
        'safety_incidents' => 'array',
        'quality_issues' => 'array',
        'delays' => 'array',
        'concerns' => 'array',
        'photos' => 'array',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function preparer(): BelongsTo { return $this->belongsTo(User::class, 'prepared_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeApproved($query) { return $query->where('status', 'approved'); }
}
