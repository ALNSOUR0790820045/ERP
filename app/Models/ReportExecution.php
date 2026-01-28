<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExecution extends Model
{
    protected $fillable = [
        'report_definition_id',
        'parameters',
        'filters_applied',
        'started_at',
        'completed_at',
        'rows_count',
        'status',
        'error_message',
        'output_format',
        'file_path',
        'file_size',
        'executed_by',
    ];

    protected $casts = [
        'parameters' => 'array',
        'filters_applied' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function reportDefinition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class);
    }

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function getDurationAttribute(): ?int
    {
        if (!$this->completed_at) return null;
        return $this->started_at->diffInSeconds($this->completed_at);
    }
}
