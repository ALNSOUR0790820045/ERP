<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'issue_number', 'issue_category', 'title', 'description',
        'priority', 'severity', 'raised_by', 'raised_date',
        'assigned_to', 'target_resolution_date', 'actual_resolution_date',
        'resolution', 'impact_description', 'cost_impact', 'time_impact_days',
        'related_risk_id', 'status', 'notes',
    ];

    protected $casts = [
        'raised_date' => 'date',
        'target_resolution_date' => 'date',
        'actual_resolution_date' => 'date',
        'cost_impact' => 'decimal:3',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function raiser(): BelongsTo { return $this->belongsTo(User::class, 'raised_by'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function relatedRisk(): BelongsTo { return $this->belongsTo(ProjectRisk::class, 'related_risk_id'); }

    public function scopeOpen($query) { return $query->where('status', 'open'); }
    public function scopeCritical($query) { return $query->where('severity', 'critical'); }
    public function scopeOverdue($query) { 
        return $query->where('status', 'open')
            ->where('target_resolution_date', '<', now()); 
    }
}
