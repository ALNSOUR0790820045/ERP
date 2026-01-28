<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectMeeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'meeting_number', 'meeting_type', 'title',
        'description', 'meeting_date', 'start_time', 'end_time',
        'location', 'virtual_link', 'organizer_id', 'attendees',
        'agenda', 'status', 'notes',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'attendees' => 'array',
        'agenda' => 'array',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function organizer(): BelongsTo { return $this->belongsTo(User::class, 'organizer_id'); }
    public function minutes(): HasMany { return $this->hasMany(MeetingMinutes::class); }

    public function scopeUpcoming($query) { return $query->where('meeting_date', '>=', now()); }
    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
}
