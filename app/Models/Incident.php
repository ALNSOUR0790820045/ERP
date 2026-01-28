<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    protected $fillable = [
        'project_id', 'incident_number', 'incident_datetime', 'incident_type',
        'severity', 'location', 'description', 'immediate_actions',
        'injury_occurred', 'injuries_count', 'injury_details',
        'property_damage', 'damage_cost', 'damage_details',
        'work_stopped', 'lost_hours', 'root_cause', 'corrective_actions',
        'preventive_actions', 'status', 'reported_by', 'investigated_by', 'closed_date',
    ];

    protected $casts = [
        'incident_datetime' => 'datetime',
        'closed_date' => 'date',
        'injury_occurred' => 'boolean',
        'property_damage' => 'boolean',
        'work_stopped' => 'boolean',
        'damage_cost' => 'decimal:3',
        'lost_hours' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function persons(): HasMany
    {
        return $this->hasMany(IncidentPerson::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function investigatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigated_by');
    }
}
