<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LookAhead extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'look_ahead_number', 'period_weeks', 'start_date', 'end_date',
        'data_date', 'activities', 'constraints', 'required_resources',
        'material_requirements', 'equipment_requirements', 'labor_requirements',
        'potential_delays', 'mitigation_actions', 'status',
        'prepared_by', 'approved_by', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'data_date' => 'date',
        'activities' => 'array',
        'constraints' => 'array',
        'required_resources' => 'array',
        'material_requirements' => 'array',
        'equipment_requirements' => 'array',
        'labor_requirements' => 'array',
        'potential_delays' => 'array',
        'mitigation_actions' => 'array',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function preparer(): BelongsTo { return $this->belongsTo(User::class, 'prepared_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
