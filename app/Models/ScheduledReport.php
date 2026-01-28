<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledReport extends Model
{
    protected $fillable = [
        'report_definition_id',
        'name',
        'frequency',
        'cron_expression',
        'run_time',
        'day_of_week',
        'day_of_month',
        'parameters',
        'recipients',
        'output_format',
        'is_active',
        'last_run',
        'next_run',
        'created_by',
    ];

    protected $casts = [
        'run_time' => 'datetime:H:i',
        'parameters' => 'array',
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_run' => 'datetime',
        'next_run' => 'datetime',
    ];

    public function reportDefinition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
