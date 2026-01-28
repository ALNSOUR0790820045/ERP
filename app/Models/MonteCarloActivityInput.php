<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonteCarloActivityInput extends Model
{
    use HasFactory;

    protected $table = 'monte_carlo_activity_inputs';

    protected $fillable = [
        'monte_carlo_simulation_id',
        'gantt_task_id',
        'optimistic_duration',
        'most_likely_duration',
        'pessimistic_duration',
        'optimistic_cost',
        'most_likely_cost',
        'pessimistic_cost',
        'correlation_coefficient',
        'is_critical_driver',
    ];

    protected $casts = [
        'optimistic_cost' => 'decimal:2',
        'most_likely_cost' => 'decimal:2',
        'pessimistic_cost' => 'decimal:2',
        'is_critical_driver' => 'boolean',
    ];

    // Relationships
    public function simulation(): BelongsTo
    {
        return $this->belongsTo(MonteCarloSimulation::class, 'monte_carlo_simulation_id');
    }

    public function ganttTask(): BelongsTo
    {
        return $this->belongsTo(GanttTask::class);
    }

    // Scopes
    public function scopeCriticalDrivers($query)
    {
        return $query->where('is_critical_driver', true);
    }

    // Methods
    public function getDurationRangeAttribute(): int
    {
        return $this->pessimistic_duration - $this->optimistic_duration;
    }

    public function getCostRangeAttribute(): ?float
    {
        if (!$this->pessimistic_cost || !$this->optimistic_cost) {
            return null;
        }
        return $this->pessimistic_cost - $this->optimistic_cost;
    }

    /**
     * Calculate PERT expected duration using beta distribution
     */
    public function getPertDurationAttribute(): float
    {
        return ($this->optimistic_duration + (4 * $this->most_likely_duration) + $this->pessimistic_duration) / 6;
    }

    /**
     * Calculate PERT standard deviation for duration
     */
    public function getDurationStandardDeviationAttribute(): float
    {
        return ($this->pessimistic_duration - $this->optimistic_duration) / 6;
    }

    /**
     * Calculate PERT expected cost using beta distribution
     */
    public function getPertCostAttribute(): ?float
    {
        if (!$this->optimistic_cost || !$this->most_likely_cost || !$this->pessimistic_cost) {
            return null;
        }
        return ($this->optimistic_cost + (4 * $this->most_likely_cost) + $this->pessimistic_cost) / 6;
    }
}
