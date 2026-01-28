<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyMeetingAttendee extends Model
{
    protected $fillable = [
        'meeting_id', 'employee_id', 'attendee_name', 'company', 'trade',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(SafetyMeeting::class, 'meeting_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
