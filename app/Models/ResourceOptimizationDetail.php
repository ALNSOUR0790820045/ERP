<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceOptimizationDetail extends Model
{
    use HasFactory;

    protected $table = 'resource_optimization_details';

    protected $fillable = [
        'resource_optimization_id',
        'gantt_task_id',
        'project_resource_id',
        'original_start_date',
        'original_end_date',
        'optimized_start_date',
        'optimized_end_date',
        'delay_days',
        'original_units',
        'optimized_units',
        'change_reason',
        'is_applied',
    ];

    protected $casts = [
        'original_start_date' => 'date',
        'original_end_date' => 'date',
        'optimized_start_date' => 'date',
        'optimized_end_date' => 'date',
        'original_units' => 'decimal:2',
        'optimized_units' => 'decimal:2',
        'is_applied' => 'boolean',
    ];

    // Relationships
    public function optimization(): BelongsTo
    {
        return $this->belongsTo(ResourceOptimization::class, 'resource_optimization_id');
    }

    public function ganttTask(): BelongsTo
    {
        return $this->belongsTo(GanttTask::class);
    }

    public function projectResource(): BelongsTo
    {
        return $this->belongsTo(ProjectResource::class);
    }

    // Scopes
    public function scopeApplied($query)
    {
        return $query->where('is_applied', true);
    }

    public function scopeNotApplied($query)
    {
        return $query->where('is_applied', false);
    }

    public function scopeDelayed($query)
    {
        return $query->where('delay_days', '>', 0);
    }

    public function scopeAdvanced($query)
    {
        return $query->where('delay_days', '<', 0);
    }

    // Methods
    public function apply(): void
    {
        if ($this->ganttTask && !$this->is_applied) {
            $this->ganttTask->update([
                'start_date' => $this->optimized_start_date,
                'end_date' => $this->optimized_end_date,
            ]);
            $this->update(['is_applied' => true]);
        }
    }

    public function getDelayDirectionAttribute(): string
    {
        if ($this->delay_days > 0) {
            return 'تأخير';
        } elseif ($this->delay_days < 0) {
            return 'تقديم';
        }
        return 'بدون تغيير';
    }

    public function hasDateChange(): bool
    {
        return $this->original_start_date != $this->optimized_start_date
            || $this->original_end_date != $this->optimized_end_date;
    }

    public function hasUnitsChange(): bool
    {
        return $this->original_units != $this->optimized_units;
    }
}
