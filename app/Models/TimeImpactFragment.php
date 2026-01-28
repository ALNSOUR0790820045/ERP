<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeImpactFragment extends Model
{
    use HasFactory;

    protected $table = 'time_impact_fragments';

    protected $fillable = [
        'time_impact_analysis_id',
        'fragment_id',
        'fragment_name',
        'predecessor_task_id',
        'successor_task_id',
        'fragment_start_date',
        'fragment_end_date',
        'fragment_duration',
        'dependency_type',
        'lag_days',
        'description',
    ];

    protected $casts = [
        'fragment_start_date' => 'date',
        'fragment_end_date' => 'date',
    ];

    // Relationships
    public function timeImpactAnalysis(): BelongsTo
    {
        return $this->belongsTo(TimeImpactAnalysis::class);
    }

    public function predecessorTask(): BelongsTo
    {
        return $this->belongsTo(GanttTask::class, 'predecessor_task_id');
    }

    public function successorTask(): BelongsTo
    {
        return $this->belongsTo(GanttTask::class, 'successor_task_id');
    }

    // Scopes
    public function scopeFinishToStart($query)
    {
        return $query->where('dependency_type', 'FS');
    }

    public function scopeStartToStart($query)
    {
        return $query->where('dependency_type', 'SS');
    }

    public function scopeWithLag($query)
    {
        return $query->where('lag_days', '>', 0);
    }

    // Methods
    public function getDependencyTypeArabicAttribute(): string
    {
        $types = [
            'FS' => 'نهاية-بداية',
            'FF' => 'نهاية-نهاية',
            'SS' => 'بداية-بداية',
            'SF' => 'بداية-نهاية',
        ];

        return $types[$this->dependency_type] ?? $this->dependency_type;
    }

    public function recalculateDuration(): void
    {
        if ($this->fragment_start_date && $this->fragment_end_date) {
            $this->fragment_duration = $this->fragment_start_date->diffInDays($this->fragment_end_date);
            $this->save();
        }
    }
}
