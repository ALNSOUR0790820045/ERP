<?php

namespace App\Services\ProjectManagement;

use App\Models\MonteCarloSimulation;
use App\Models\MonteCarloActivityInput;
use App\Models\MonteCarloResult;
use App\Models\Project;
use App\Models\GanttTask;
use Illuminate\Support\Facades\DB;

class MonteCarloService
{
    protected ?MonteCarloSimulation $simulation = null;
    protected array $activityInputs = [];
    protected array $dependencies = [];
    protected array $criticalPathCounts = [];

    /**
     * Create a new Monte Carlo simulation
     */
    public function createSimulation(int $projectId, array $data): MonteCarloSimulation
    {
        $project = Project::with('ganttTasks')->findOrFail($projectId);
        
        $this->simulation = MonteCarloSimulation::create([
            'project_id' => $projectId,
            'name' => $data['name'] ?? 'Monte Carlo Simulation - ' . now()->format('Y-m-d'),
            'description' => $data['description'] ?? null,
            'iterations' => $data['iterations'] ?? 1000,
            'distribution_type' => $data['distribution_type'] ?? 'triangular',
            'confidence_level' => $data['confidence_level'] ?? 80.00,
            'baseline_finish_date' => $project->end_date,
            'baseline_cost' => $project->budget ?? $project->ganttTasks->sum('budgeted_cost'),
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        return $this->simulation;
    }

    /**
     * Add activity input for simulation
     */
    public function addActivityInput(int $simulationId, array $data): MonteCarloActivityInput
    {
        return MonteCarloActivityInput::create([
            'monte_carlo_simulation_id' => $simulationId,
            'gantt_task_id' => $data['gantt_task_id'],
            'optimistic_duration' => $data['optimistic_duration'],
            'most_likely_duration' => $data['most_likely_duration'],
            'pessimistic_duration' => $data['pessimistic_duration'],
            'optimistic_cost' => $data['optimistic_cost'] ?? null,
            'most_likely_cost' => $data['most_likely_cost'] ?? null,
            'pessimistic_cost' => $data['pessimistic_cost'] ?? null,
            'correlation_coefficient' => $data['correlation_coefficient'] ?? null,
            'is_critical_driver' => $data['is_critical_driver'] ?? false,
        ]);
    }

    /**
     * Auto-generate activity inputs based on task durations
     */
    public function autoGenerateInputs(int $simulationId, float $variancePercent = 20): void
    {
        $simulation = MonteCarloSimulation::findOrFail($simulationId);
        $tasks = GanttTask::where('project_id', $simulation->project_id)
            ->where('is_milestone', false)
            ->where('is_summary', false)
            ->get();

        foreach ($tasks as $task) {
            $baseDuration = $task->duration ?? 1;
            $baseCost = $task->budgeted_cost ?? 0;
            
            $varianceFactor = $variancePercent / 100;
            
            MonteCarloActivityInput::create([
                'monte_carlo_simulation_id' => $simulationId,
                'gantt_task_id' => $task->id,
                'optimistic_duration' => max(1, (int) ($baseDuration * (1 - $varianceFactor))),
                'most_likely_duration' => $baseDuration,
                'pessimistic_duration' => (int) ($baseDuration * (1 + $varianceFactor * 1.5)),
                'optimistic_cost' => $baseCost > 0 ? $baseCost * (1 - $varianceFactor * 0.5) : null,
                'most_likely_cost' => $baseCost > 0 ? $baseCost : null,
                'pessimistic_cost' => $baseCost > 0 ? $baseCost * (1 + $varianceFactor) : null,
                'is_critical_driver' => $task->is_critical ?? false,
            ]);
        }
    }

    /**
     * Run Monte Carlo simulation
     */
    public function runSimulation(int $simulationId): MonteCarloSimulation
    {
        $this->simulation = MonteCarloSimulation::with([
            'activityInputs.ganttTask.dependencies',
            'project.ganttTasks',
        ])->findOrFail($simulationId);

        $this->simulation->startSimulation();

        try {
            // Load inputs and build dependency graph
            $this->loadActivityInputs();
            $this->buildDependencyGraph();
            
            $results = [];
            $finishDates = [];
            $costs = [];

            for ($i = 1; $i <= $this->simulation->iterations; $i++) {
                $iterationResult = $this->runIteration($i);
                $results[] = $iterationResult;
                $finishDates[] = $iterationResult['finish_date'];
                $costs[] = $iterationResult['total_cost'];
                
                // Store result
                MonteCarloResult::create([
                    'monte_carlo_simulation_id' => $simulationId,
                    'iteration_number' => $i,
                    'simulated_finish_date' => $iterationResult['finish_date'],
                    'simulated_cost' => $iterationResult['total_cost'],
                    'simulated_duration_days' => $iterationResult['duration_days'],
                    'critical_path_activities' => $iterationResult['critical_path'],
                    'activity_durations' => $iterationResult['activity_durations'],
                ]);
            }

            // Calculate statistics
            sort($finishDates);
            sort($costs);
            
            $p50Index = (int) (0.50 * count($finishDates));
            $p80Index = (int) (0.80 * count($finishDates));
            $p90Index = (int) (0.90 * count($finishDates));

            $this->simulation->update([
                'p50_finish_date' => $finishDates[$p50Index] ?? null,
                'p80_finish_date' => $finishDates[$p80Index] ?? null,
                'p90_finish_date' => $finishDates[$p90Index] ?? null,
                'p50_cost' => $costs[$p50Index] ?? null,
                'p80_cost' => $costs[$p80Index] ?? null,
                'p90_cost' => $costs[$p90Index] ?? null,
                'results' => [
                    'mean_finish_date' => $this->calculateMeanDate($finishDates),
                    'std_dev_days' => $this->calculateStdDevDays($finishDates),
                    'mean_cost' => array_sum($costs) / count($costs),
                    'std_dev_cost' => $this->calculateStdDev($costs),
                    'criticality_index' => $this->calculateCriticalityIndex(),
                ],
            ]);

            $this->simulation->completeSimulation([
                'iterations_completed' => $this->simulation->iterations,
            ]);

        } catch (\Exception $e) {
            $this->simulation->failSimulation();
            throw $e;
        }

        return $this->simulation->fresh();
    }

    /**
     * Load activity inputs into memory
     */
    protected function loadActivityInputs(): void
    {
        foreach ($this->simulation->activityInputs as $input) {
            $this->activityInputs[$input->gantt_task_id] = $input;
        }
    }

    /**
     * Build dependency graph
     */
    protected function buildDependencyGraph(): void
    {
        foreach ($this->simulation->project->ganttTasks as $task) {
            $this->dependencies[$task->id] = [];
            
            foreach ($task->dependencies ?? [] as $dep) {
                $this->dependencies[$task->id][] = $dep->predecessor_id;
            }
        }
    }

    /**
     * Run a single iteration of the simulation
     */
    protected function runIteration(int $iterationNumber): array
    {
        $activityDurations = [];
        $activityCosts = [];
        $startDates = [];
        $endDates = [];
        
        // Generate random durations for each activity
        foreach ($this->activityInputs as $taskId => $input) {
            $activityDurations[$taskId] = $this->generateRandomDuration($input);
            $activityCosts[$taskId] = $this->generateRandomCost($input);
        }

        // Calculate schedule using CPM
        $projectStart = $this->simulation->project->start_date ?? now();
        
        // Forward pass
        foreach ($this->getTopologicalOrder() as $taskId) {
            $predecessorEnds = [];
            
            foreach ($this->dependencies[$taskId] ?? [] as $predId) {
                if (isset($endDates[$predId])) {
                    $predecessorEnds[] = $endDates[$predId];
                }
            }
            
            $startDates[$taskId] = empty($predecessorEnds) 
                ? $projectStart 
                : max($predecessorEnds);
                
            $duration = $activityDurations[$taskId] ?? $this->activityInputs[$taskId]->ganttTask->duration ?? 1;
            $endDates[$taskId] = $this->addDays($startDates[$taskId], $duration);
        }

        // Find project finish date
        $projectFinish = !empty($endDates) ? max($endDates) : $projectStart;
        
        // Identify critical path
        $criticalPath = $this->identifyCriticalPath($endDates, $projectFinish);
        
        // Track critical path for criticality index
        foreach ($criticalPath as $taskId) {
            if (!isset($this->criticalPathCounts[$taskId])) {
                $this->criticalPathCounts[$taskId] = 0;
            }
            $this->criticalPathCounts[$taskId]++;
        }

        return [
            'finish_date' => $projectFinish,
            'duration_days' => $this->daysBetween($projectStart, $projectFinish),
            'total_cost' => array_sum($activityCosts),
            'critical_path' => $criticalPath,
            'activity_durations' => $activityDurations,
        ];
    }

    /**
     * Generate random duration based on distribution
     */
    protected function generateRandomDuration(MonteCarloActivityInput $input): int
    {
        return match ($this->simulation->distribution_type) {
            'triangular' => $this->triangularDistribution(
                $input->optimistic_duration,
                $input->most_likely_duration,
                $input->pessimistic_duration
            ),
            'beta', 'pert' => $this->betaPertDistribution(
                $input->optimistic_duration,
                $input->most_likely_duration,
                $input->pessimistic_duration
            ),
            'normal' => $this->normalDistribution(
                $input->most_likely_duration,
                ($input->pessimistic_duration - $input->optimistic_duration) / 6
            ),
            'uniform' => $this->uniformDistribution(
                $input->optimistic_duration,
                $input->pessimistic_duration
            ),
            default => $input->most_likely_duration,
        };
    }

    /**
     * Generate random cost based on distribution
     */
    protected function generateRandomCost(MonteCarloActivityInput $input): float
    {
        if (!$input->most_likely_cost) {
            return 0;
        }

        return match ($this->simulation->distribution_type) {
            'triangular' => $this->triangularDistribution(
                $input->optimistic_cost,
                $input->most_likely_cost,
                $input->pessimistic_cost
            ),
            'beta', 'pert' => $this->betaPertDistribution(
                $input->optimistic_cost,
                $input->most_likely_cost,
                $input->pessimistic_cost
            ),
            default => $input->most_likely_cost,
        };
    }

    /**
     * Triangular distribution
     */
    protected function triangularDistribution(float $min, float $mode, float $max): float
    {
        $u = mt_rand() / mt_getrandmax();
        $fc = ($mode - $min) / ($max - $min);
        
        if ($u < $fc) {
            return $min + sqrt($u * ($max - $min) * ($mode - $min));
        }
        
        return $max - sqrt((1 - $u) * ($max - $min) * ($max - $mode));
    }

    /**
     * Beta-PERT distribution
     */
    protected function betaPertDistribution(float $min, float $mode, float $max): float
    {
        $mean = ($min + 4 * $mode + $max) / 6;
        $stdDev = ($max - $min) / 6;
        
        // Approximate beta distribution using normal
        $value = $this->normalDistribution($mean, $stdDev);
        
        return max($min, min($max, $value));
    }

    /**
     * Normal distribution using Box-Muller transform
     */
    protected function normalDistribution(float $mean, float $stdDev): float
    {
        $u1 = mt_rand() / mt_getrandmax();
        $u2 = mt_rand() / mt_getrandmax();
        
        $z = sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);
        
        return $mean + $stdDev * $z;
    }

    /**
     * Uniform distribution
     */
    protected function uniformDistribution(float $min, float $max): float
    {
        return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
    }

    /**
     * Get topological order of tasks
     */
    protected function getTopologicalOrder(): array
    {
        $visited = [];
        $order = [];
        
        $visit = function ($taskId) use (&$visit, &$visited, &$order) {
            if (isset($visited[$taskId])) {
                return;
            }
            
            $visited[$taskId] = true;
            
            foreach ($this->dependencies[$taskId] ?? [] as $predId) {
                $visit($predId);
            }
            
            $order[] = $taskId;
        };
        
        foreach (array_keys($this->activityInputs) as $taskId) {
            $visit($taskId);
        }
        
        return $order;
    }

    /**
     * Identify critical path
     */
    protected function identifyCriticalPath(array $endDates, $projectFinish): array
    {
        $criticalTasks = [];
        
        foreach ($endDates as $taskId => $endDate) {
            // Tasks that finish at project finish are on critical path
            if ($this->daysBetween($endDate, $projectFinish) <= 0) {
                $criticalTasks[] = $taskId;
            }
        }
        
        return $criticalTasks;
    }

    /**
     * Calculate criticality index for each activity
     */
    protected function calculateCriticalityIndex(): array
    {
        $index = [];
        $totalIterations = $this->simulation->iterations;
        
        foreach ($this->criticalPathCounts as $taskId => $count) {
            $index[$taskId] = round(($count / $totalIterations) * 100, 2);
        }
        
        arsort($index);
        
        return $index;
    }

    protected function addDays($date, int $days)
    {
        return \Carbon\Carbon::parse($date)->addDays($days);
    }

    protected function daysBetween($start, $end): int
    {
        return \Carbon\Carbon::parse($start)->diffInDays(\Carbon\Carbon::parse($end));
    }

    protected function calculateMeanDate(array $dates): string
    {
        $timestamps = array_map(fn($d) => strtotime($d instanceof \DateTime ? $d->format('Y-m-d') : $d), $dates);
        $meanTimestamp = array_sum($timestamps) / count($timestamps);
        return date('Y-m-d', (int) $meanTimestamp);
    }

    protected function calculateStdDevDays(array $dates): float
    {
        $baseDays = array_map(fn($d) => $this->daysBetween($this->simulation->project->start_date, $d), $dates);
        return $this->calculateStdDev($baseDays);
    }

    protected function calculateStdDev(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0;
        
        $mean = array_sum($values) / $n;
        $squaredDiffs = array_map(fn($v) => pow($v - $mean, 2), $values);
        
        return sqrt(array_sum($squaredDiffs) / ($n - 1));
    }

    /**
     * Get histogram data for finish dates
     */
    public function getFinishDateHistogram(int $simulationId, int $bins = 20): array
    {
        $results = MonteCarloResult::where('monte_carlo_simulation_id', $simulationId)
            ->orderBy('simulated_finish_date')
            ->get();

        if ($results->isEmpty()) {
            return [];
        }

        $minDate = $results->first()->simulated_finish_date;
        $maxDate = $results->last()->simulated_finish_date;
        $range = $this->daysBetween($minDate, $maxDate);
        $binSize = max(1, ceil($range / $bins));

        $histogram = [];
        foreach ($results as $result) {
            $bin = (int) floor($this->daysBetween($minDate, $result->simulated_finish_date) / $binSize);
            if (!isset($histogram[$bin])) {
                $histogram[$bin] = [
                    'date_range' => \Carbon\Carbon::parse($minDate)->addDays($bin * $binSize)->format('Y-m-d'),
                    'count' => 0,
                ];
            }
            $histogram[$bin]['count']++;
        }

        return array_values($histogram);
    }

    /**
     * Get S-curve data (cumulative probability)
     */
    public function getScurveData(int $simulationId): array
    {
        $results = MonteCarloResult::where('monte_carlo_simulation_id', $simulationId)
            ->orderBy('simulated_finish_date')
            ->get();

        $total = $results->count();
        $data = [];
        $cumulative = 0;

        foreach ($results as $result) {
            $cumulative++;
            $data[] = [
                'date' => $result->simulated_finish_date->format('Y-m-d'),
                'probability' => round(($cumulative / $total) * 100, 2),
            ];
        }

        return $data;
    }
}
