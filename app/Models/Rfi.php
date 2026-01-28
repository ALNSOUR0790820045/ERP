<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rfi extends Model
{
    protected $table = 'rfis';

    protected $fillable = [
        'project_id', 'rfi_number', 'rfi_date', 'subject', 'question',
        'discipline', 'location', 'priority', 'required_date', 'response_date',
        'response', 'status', 'raised_by', 'assigned_to', 'responded_by',
    ];

    protected $casts = [
        'rfi_date' => 'date',
        'required_date' => 'date',
        'response_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }
}
