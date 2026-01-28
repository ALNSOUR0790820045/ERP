<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonteCarloResult extends Model
{
    use HasFactory;

    protected $table = 'monte_carlo_results';

    protected $fillable = [
        'monte_carlo_simulation_id',
        'iteration_number',
        'simulated_finish_date',
        'simulated_cost',
        'simulated_duration_days',
        'critical_path_activities',
        'activity_durations',
    ];

    protected $casts = [
        'simulated_finish_date' => 'date',
        'simulated_cost' => 'decimal:2',
        'critical_path_activities' => 'array',
        'activity_durations' => 'array',
    ];

    // Relationships
    public function simulation(): BelongsTo
    {
        return $this->belongsTo(MonteCarloSimulation::class, 'monte_carlo_simulation_id');
    }

    // Scopes
    public function scopeForSimulation($query, int $simulationId)
    {
        return $query->where('monte_carlo_simulation_id', $simulationId);
    }

    public function scopeOrderByIteration($query)
    {
        return $query->orderBy('iteration_number');
    }

    public function scopeOrderByFinishDate($query)
    {
        return $query->orderBy('simulated_finish_date');
    }

    public function scopeOrderByCost($query)
    {
        return $query->orderBy('simulated_cost');
    }

    // Static Methods for Analysis
    public static function getPercentileFinishDate(int $simulationId, int $percentile): ?string
    {
        $totalResults = self::forSimulation($simulationId)->count();
        if ($totalResults == 0) {
            return null;
        }
        
        $offset = (int) floor(($percentile / 100) * $totalResults);
        
        return self::forSimulation($simulationId)
            ->orderByFinishDate()
            ->skip($offset)
            ->first()
            ?->simulated_finish_date
            ?->format('Y-m-d');
    }

    public static function getPercentileCost(int $simulationId, int $percentile): ?float
    {
        $totalResults = self::forSimulation($simulationId)->count();
        if ($totalResults == 0) {
            return null;
        }
        
        $offset = (int) floor(($percentile / 100) * $totalResults);
        
        return self::forSimulation($simulationId)
            ->orderByCost()
            ->skip($offset)
            ->first()
            ?->simulated_cost;
    }

    public static function getCriticalityIndex(int $simulationId): array
    {
        $results = self::forSimulation($simulationId)->get();
        $totalIterations = $results->count();
        
        if ($totalIterations == 0) {
            return [];
        }
        
        $activityCounts = [];
        
        foreach ($results as $result) {
            foreach ($result->critical_path_activities ?? [] as $activityId) {
                if (!isset($activityCounts[$activityId])) {
                    $activityCounts[$activityId] = 0;
                }
                $activityCounts[$activityId]++;
            }
        }
        
        // Calculate percentage
        foreach ($activityCounts as $activityId => $count) {
            $activityCounts[$activityId] = round(($count / $totalIterations) * 100, 2);
        }
        
        arsort($activityCounts);
        
        return $activityCounts;
    }
}
