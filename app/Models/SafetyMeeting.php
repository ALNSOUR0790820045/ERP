<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SafetyMeeting extends Model
{
    protected $fillable = [
        'project_id', 'meeting_number', 'meeting_date', 'start_time',
        'end_time', 'meeting_type', 'topic', 'agenda', 'discussion_points',
        'action_items', 'attendees_count', 'conducted_by',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function conductedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conducted_by');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(SafetyMeetingAttendee::class, 'meeting_id');
    }
}
