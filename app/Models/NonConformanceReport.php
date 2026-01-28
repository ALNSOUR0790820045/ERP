<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NonConformanceReport extends Model
{
    protected $table = 'non_conformance_reports';

    protected $fillable = [
        'project_id', 'inspection_id', 'ncr_number', 'ncr_date', 'category',
        'severity', 'description', 'root_cause', 'immediate_action',
        'corrective_action', 'preventive_action', 'location', 'work_activity',
        'responsible_party', 'status', 'target_close_date', 'actual_close_date',
        'raised_by', 'assigned_to', 'closed_by',
    ];

    protected $casts = [
        'ncr_date' => 'date',
        'target_close_date' => 'date',
        'actual_close_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(QualityInspection::class, 'inspection_id');
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
