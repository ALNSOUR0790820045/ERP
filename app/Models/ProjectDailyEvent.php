<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDailyEvent extends Model
{
    use HasFactory;

    protected $table = 'project_daily_events';

    protected $fillable = [
        'daily_report_id',
        'event_type',
        'event_time',
        'title',
        'description',
        'persons_involved',
    ];

    protected $casts = [
        'event_time' => 'datetime:H:i',
    ];

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(ProjectDailyReport::class, 'daily_report_id');
    }
}
