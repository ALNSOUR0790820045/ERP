<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'report_number', 'month', 'year',
        'period_start', 'period_end', 'executive_summary',
        'planned_progress', 'actual_progress', 'variance',
        'cumulative_planned', 'cumulative_actual', 'cumulative_variance',
        'key_achievements', 'key_challenges', 'risks_issues',
        'cost_summary', 'schedule_summary', 'quality_summary', 'safety_summary',
        'procurement_status', 'subcontractor_status', 'resource_utilization',
        'photos', 'attachments', 'next_month_plan',
        'status', 'prepared_by', 'reviewed_by', 'approved_by', 'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'planned_progress' => 'decimal:2',
        'actual_progress' => 'decimal:2',
        'variance' => 'decimal:2',
        'cumulative_planned' => 'decimal:2',
        'cumulative_actual' => 'decimal:2',
        'cumulative_variance' => 'decimal:2',
        'key_achievements' => 'array',
        'key_challenges' => 'array',
        'risks_issues' => 'array',
        'cost_summary' => 'array',
        'schedule_summary' => 'array',
        'quality_summary' => 'array',
        'safety_summary' => 'array',
        'procurement_status' => 'array',
        'subcontractor_status' => 'array',
        'resource_utilization' => 'array',
        'photos' => 'array',
        'attachments' => 'array',
        'next_month_plan' => 'array',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function preparer(): BelongsTo { return $this->belongsTo(User::class, 'prepared_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeApproved($query) { return $query->where('status', 'approved'); }
}
