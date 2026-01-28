<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceOptimization extends Model
{
    use HasFactory;

    protected $table = 'resource_optimizations';

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'optimization_type',
        'priority',
        'optimization_start_date',
        'optimization_end_date',
        'respect_dependencies',
        'level_within_slack',
        'max_delay_days',
        'status',
        'original_finish_date',
        'optimized_finish_date',
        'original_cost',
        'optimized_cost',
        'resource_utilization_before',
        'resource_utilization_after',
        'overallocations_before',
        'overallocations_after',
        'optimization_results',
        'conflicts_resolved',
        'created_by',
        'started_at',
        'completed_at',
        'applied_at',
    ];

    protected $casts = [
        'optimization_start_date' => 'date',
        'optimization_end_date' => 'date',
        'respect_dependencies' => 'boolean',
        'level_within_slack' => 'boolean',
        'original_finish_date' => 'date',
        'optimized_finish_date' => 'date',
        'original_cost' => 'decimal:2',
        'optimized_cost' => 'decimal:2',
        'optimization_results' => 'array',
        'conflicts_resolved' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(ResourceOptimizationDetail::class);
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeApplied($query)
    {
        return $query->where('status', 'applied');
    }

    public function scopeLeveling($query)
    {
        return $query->where('optimization_type', 'leveling');
    }

    public function scopeSmoothing($query)
    {
        return $query->where('optimization_type', 'smoothing');
    }

    // Methods
    public function startOptimization(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function completeOptimization(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function applyOptimization(): void
    {
        // Apply all detail changes to actual gantt tasks
        foreach ($this->details as $detail) {
            if (!$detail->is_applied && $detail->ganttTask) {
                $detail->ganttTask->update([
                    'start_date' => $detail->optimized_start_date,
                    'end_date' => $detail->optimized_end_date,
                ]);
                $detail->update(['is_applied' => true]);
            }
        }

        $this->update([
            'status' => 'applied',
            'applied_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }

    public function getScheduleImpactDaysAttribute(): ?int
    {
        if (!$this->optimized_finish_date || !$this->original_finish_date) {
            return null;
        }
        return $this->optimized_finish_date->diffInDays($this->original_finish_date, false);
    }

    public function getCostSavingsAttribute(): ?float
    {
        if (!$this->original_cost || !$this->optimized_cost) {
            return null;
        }
        return $this->original_cost - $this->optimized_cost;
    }

    public function getUtilizationImprovementAttribute(): ?float
    {
        if ($this->resource_utilization_before === null || $this->resource_utilization_after === null) {
            return null;
        }
        return $this->resource_utilization_after - $this->resource_utilization_before;
    }

    public function getOverallocationsResolvedAttribute(): int
    {
        return max(0, $this->overallocations_before - $this->overallocations_after);
    }
}
