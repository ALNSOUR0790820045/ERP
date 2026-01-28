<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_calendar_id', 'project_id', 'name', 'holiday_date',
        'holiday_type', 'is_recurring', 'notes',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    public function projectCalendar(): BelongsTo { return $this->belongsTo(ProjectCalendar::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
}
