<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonteCarloSimulation extends Model
{
    use HasFactory;

    protected $table = 'monte_carlo_simulations';

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'iterations',
        'distribution_type',
        'confidence_level',
        'baseline_finish_date',
        'baseline_cost',
        'status',
        'input_parameters',
        'results',
        'p50_finish_date',
        'p80_finish_date',
        'p90_finish_date',
        'p50_cost',
        'p80_cost',
        'p90_cost',
        'schedule_risk_days',
        'cost_risk_amount',
        'criticality_index',
        'created_by',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'baseline_finish_date' => 'date',
        'baseline_cost' => 'decimal:2',
        'input_parameters' => 'array',
        'results' => 'array',
        'p50_finish_date' => 'date',
        'p80_finish_date' => 'date',
        'p90_finish_date' => 'date',
        'p50_cost' => 'decimal:2',
        'p80_cost' => 'decimal:2',
        'p90_cost' => 'decimal:2',
        'cost_risk_amount' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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

    public function activityInputs(): HasMany
    {
        return $this->hasMany(MonteCarloActivityInput::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(MonteCarloResult::class);
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

    // Methods
    public function startSimulation(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function completeSimulation(array $results): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'results' => $results,
        ]);
    }

    public function failSimulation(): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
        ]);
    }

    public function getScheduleRiskDaysAttribute(): ?int
    {
        if (!$this->p80_finish_date || !$this->baseline_finish_date) {
            return null;
        }
        return $this->p80_finish_date->diffInDays($this->baseline_finish_date);
    }

    public function getCostRiskPercentAttribute(): ?float
    {
        if (!$this->p80_cost || !$this->baseline_cost || $this->baseline_cost == 0) {
            return null;
        }
        return round((($this->p80_cost - $this->baseline_cost) / $this->baseline_cost) * 100, 2);
    }
}
