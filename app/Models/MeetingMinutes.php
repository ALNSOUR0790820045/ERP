<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingMinutes extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_meeting_id', 'minutes_number', 'actual_attendees',
        'absent_attendees', 'discussion_points', 'decisions_made',
        'action_items', 'next_meeting_date', 'attachments',
        'prepared_by', 'approved_by', 'approved_at', 'status', 'notes',
    ];

    protected $casts = [
        'next_meeting_date' => 'date',
        'approved_at' => 'datetime',
        'actual_attendees' => 'array',
        'absent_attendees' => 'array',
        'discussion_points' => 'array',
        'decisions_made' => 'array',
        'action_items' => 'array',
        'attachments' => 'array',
    ];

    public function projectMeeting(): BelongsTo { return $this->belongsTo(ProjectMeeting::class); }
    public function preparer(): BelongsTo { return $this->belongsTo(User::class, 'prepared_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeApproved($query) { return $query->where('status', 'approved'); }
}
