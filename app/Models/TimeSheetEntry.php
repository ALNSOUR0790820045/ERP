<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeSheetEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'time_sheet_id', 'entry_date', 'start_time', 'end_time',
        'break_duration', 'regular_hours', 'overtime_hours', 'total_hours',
        'task_description', 'activity_type', 'is_billable', 'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'break_duration' => 'decimal:2',
        'regular_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'total_hours' => 'decimal:2',
        'is_billable' => 'boolean',
    ];

    public function timeSheet(): BelongsTo { return $this->belongsTo(TimeSheet::class); }

    public function scopeBillable($query) { return $query->where('is_billable', true); }
}
