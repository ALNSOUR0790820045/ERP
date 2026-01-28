<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkPermit extends Model
{
    protected $fillable = [
        'project_id', 'permit_number', 'permit_type', 'start_datetime',
        'end_datetime', 'location', 'work_description', 'hazards_identified',
        'precautions', 'ppe_required', 'emergency_procedures', 'status',
        'requested_by', 'approved_by', 'approved_at', 'closed_by',
        'closed_at', 'closure_remarks',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
