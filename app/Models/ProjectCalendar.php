<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectCalendar extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'name', 'calendar_type', 'working_days',
        'working_hours_start', 'working_hours_end', 'hours_per_day',
        'is_default', 'effective_from', 'effective_to', 'notes',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'working_days' => 'array',
        'hours_per_day' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function holidays(): HasMany { return $this->hasMany(ProjectHoliday::class); }

    public function scopeDefault($query) { return $query->where('is_default', true); }
}
