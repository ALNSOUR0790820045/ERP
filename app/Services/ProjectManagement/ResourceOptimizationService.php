<?php

namespace App\Services\ProjectManagement;

use App\Models\ResourceOptimization;
use App\Models\ResourceOptimizationDetail;
use App\Models\Project;
use App\Models\GanttTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ResourceOptimizationService
{
    protected ?ResourceOptimization $optimization = null;
    protected array $resourceUsage = [];
    protected array $conflicts = [];

    /**
     * Create resource optimization
     */
    public function createOptimization(int $projectId, array $data): ResourceOptimization
    {
        $project = Project::findOrFail($projectId);

        $this->optimization = ResourceOptimization::create([
            'project_id' => $projectId,
            'name' => $data['name'] ?? 'Resource Optimization - ' . now()->format('Y-m-d'),
            'description' => $data['description'] ?? null,
            'optimization_type' => $data['optimization_type'] ?? 'leveling',
            'priority' => $data['priority'] ?? 'balanced',
            'optimization_start_date' => $data['start_date'] ?? $project->start_date,
            'optimization_end_date' => $data['end_date'] ?? $project->end_date,
            'respect_dependencies' => $data['respect_dependencies'] ?? true,
            'level_within_slack' => $data['level_within_slack'] ?? true,
            'max_delay_days' => $data['max_delay_days'] ?? null,
            'status' => 'draft',
            'original_finish_date' => $project->end_date,
            'created_by' => auth()->id(),
        ]);

        return $this->optimization;
    }

    /**
     * Run resource leveling optimization
     */
    public function runLeveling(int $optimizationId): ResourceOptimization
    {
        $this->optimization = ResourceOptimization::with([
            'project.ganttTasks.resources',
            'project.ganttTasks.dependencies',
        ])->findOrFail($optimizationId);

        $this->optimization->startOptimization();

        try {
            // Analyze current resource usage
            $this->analyzeResourceUsage();
            
            // Find overallocations
            $overallocations = $this->findOverallocations();
            $this->optimization->update(['overallocations_before' => count($overallocations)]);

            // Run leveling algorithm
            $leveledTasks = $this->levelResources($overallocations);

            // Calculate results
            $this->calculateOptimizationResults($leveledTasks);

            // Analyze post-optimization
            $postOverallocations = $this->findOverallocations();
            $this->optimization->update(['overallocations_after' => count($postOverallocations)]);

            $this->optimization->completeOptimization();

        } catch (\Exception $e) {
            $this->optimization->update(['status' => 'failed']);
            throw $e;
        }

        return $this->optimization->fresh();
    }

    /**
     * Run resource smoothing optimization
     */
    public function runSmoothing(int $optimizationId): ResourceOptimization
    {
        $this->optimization = ResourceOptimization::with([
            'project.ganttTasks.resources',
        ])->findOrFail($optimizationId);

        $this->optimization->startOptimization();

        try {
            // Analyze resource usage
            $this->analyzeResourceUsage();

            // Find peaks and valleys
            $resourceProfiles = $this->getResourceProfiles();

            // Smooth within available float
            $smoothedTasks = $this->smoothResources($resourceProfiles);

            // Calculate results
            $this->calculateOptimizationResults($smoothedTasks);

            $this->optimization->completeOptimization();

        } catch (\Exception $e) {
            $this->optimization->update(['status' => 'failed']);
            throw $e;
        }

        return $this->optimization->fresh();
    }

    /**
     * Analyze current resource usage
     */
    protected function analyzeResourceUsage(): void
    {
        $this->resourceUsage = [];

        foreach ($this->optimization->project->ganttTasks as $task) {
            if (!$task->start_date || !$task->end_date) {
                continue;
            }

            $startDate = \Carbon\Carbon::parse($task->start_date);
            $endDate = \Carbon\Carbon::parse($task->end_date);

            foreach ($task->resources ?? [] as $resource) {
                $resourceName = $resource->resource_name ?? $resource->name ?? 'Unknown';
                $units = $resource->units ?? 100;

                for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                    $dateKey = $date->format('Y-m-d');
                    
                    if (!isset($this->resourceUsage[$resourceName])) {
                        $this->resourceUsage[$resourceName] = [];
                    }
                    
                    if (!isset($this->resourceUsage[$resourceName][$dateKey])) {
                        $this->resourceUsage[$resourceName][$dateKey] = [
                            'total_units' => 0,
                            'tasks' => [],
                        ];
                    }
                    
                    $this->resourceUsage[$resourceName][$dateKey]['total_units'] += $units;
                    $this->resourceUsage[$resourceName][$dateKey]['tasks'][] = $task->id;
                }
            }
        }
    }

    /**
     * Find resource overallocations
     */
    protected function findOverallocations(): array
    {
        $overallocations = [];
        $maxUnits = 100; // 100% capacity

        foreach ($this->resourceUsage as $resourceName => $dates) {
            foreach ($dates as $date => $usage) {
                if ($usage['total_units'] > $maxUnits) {
                    $overallocations[] = [
                        'resource' => $resourceName,
                        'date' => $date,
                        'total_units' => $usage['total_units'],
                        'over_by' => $usage['total_units'] - $maxUnits,
                        'tasks' => $usage['tasks'],
                    ];
                }
            }
        }

        return $overallocations;
    }

    /**
     * Level resources by delaying tasks
     */
    protected function levelResources(array $overallocations): array
    {
        $leveledTasks = [];
        $processedTasks = [];

        // Group overallocations by date
        $overByDate = collect($overallocations)->groupBy('date')->sortKeys();

        foreach ($overByDate as $date => $dateOverallocations) {
            foreach ($dateOverallocations as $over) {
                // Get tasks that can be moved
                $movableTasks = array_diff($over['tasks'], $processedTasks);
                
                if (empty($movableTasks)) {
                    continue;
                }

                // Sort by priority (move lower priority tasks first)
                $tasksToMove = GanttTask::whereIn('id', $movableTasks)
                    ->orderBy('is_critical', 'asc')
                    ->orderBy('total_float', 'desc')
                    ->get();

                foreach ($tasksToMove as $task) {
                    if ($over['total_units'] <= 100) {
                        break;
                    }

                    // Check if we can delay this task
                    if (!$this->canDelayTask($task)) {
                        continue;
                    }

                    // Calculate delay needed
                    $delayDays = $this->calculateMinimumDelay($task, $date);
                    
                    if ($this->optimization->max_delay_days && $delayDays > $this->optimization->max_delay_days) {
                        continue;
                    }

                    // Apply delay
                    $newStartDate = \Carbon\Carbon::parse($task->start_date)->addDays($delayDays);
                    $newEndDate = \Carbon\Carbon::parse($task->end_date)->addDays($delayDays);

                    $leveledTasks[$task->id] = [
                        'task' => $task,
                        'original_start' => $task->start_date,
                        'original_end' => $task->end_date,
                        'new_start' => $newStartDate->format('Y-m-d'),
                        'new_end' => $newEndDate->format('Y-m-d'),
                        'delay_days' => $delayDays,
                        'reason' => "Resource leveling - {$over['resource']} overallocated on {$date}",
                    ];

                    $processedTasks[] = $task->id;
                    $over['total_units'] -= ($task->resources->first()?->units ?? 100);
                }
            }
        }

        return $leveledTasks;
    }

    /**
     * Check if task can be delayed
     */
    protected function canDelayTask(GanttTask $task): bool
    {
        // Don't delay critical path tasks if within slack only
        if ($this->optimization->level_within_slack && $task->is_critical) {
            return false;
        }

        // Don't delay completed tasks
        if ($task->status === 'completed' || ($task->progress ?? 0) >= 100) {
            return false;
        }

        // Check dependencies
        if ($this->optimization->respect_dependencies) {
            // Task can be delayed if it has positive float
            return ($task->total_float ?? 0) > 0;
        }

        return true;
    }

    /**
     * Calculate minimum delay needed
     */
    protected function calculateMinimumDelay(GanttTask $task, string $conflictDate): int
    {
        // Simple approach: delay by 1 day, more sophisticated algorithms could be used
        $delay = 1;
        
        // If leveling within slack, limit to available float
        if ($this->optimization->level_within_slack && $task->total_float) {
            $delay = min($delay, $task->total_float);
        }

        return $delay;
    }

    /**
     * Get resource usage profiles
     */
    protected function getResourceProfiles(): array
    {
        $profiles = [];

        foreach ($this->resourceUsage as $resourceName => $dates) {
            ksort($dates);
            $profile = [
                'resource' => $resourceName,
                'daily_usage' => [],
                'peak_usage' => 0,
                'average_usage' => 0,
            ];

            $totalUsage = 0;
            foreach ($dates as $date => $usage) {
                $profile['daily_usage'][$date] = $usage['total_units'];
                $profile['peak_usage'] = max($profile['peak_usage'], $usage['total_units']);
                $totalUsage += $usage['total_units'];
            }

            $profile['average_usage'] = count($dates) > 0 ? $totalUsage / count($dates) : 0;
            $profiles[$resourceName] = $profile;
        }

        return $profiles;
    }

    /**
     * Smooth resource usage
     */
    protected function smoothResources(array $profiles): array
    {
        $smoothedTasks = [];

        foreach ($profiles as $resourceName => $profile) {
            $targetUsage = $profile['average_usage'];
            
            // Find days above target
            $peakDays = array_filter($profile['daily_usage'], fn($u) => $u > $targetUsage);
            
            // Try to move tasks from peak days to valley days
            foreach ($peakDays as $date => $usage) {
                $excess = $usage - $targetUsage;
                $tasksOnDate = $this->resourceUsage[$resourceName][$date]['tasks'] ?? [];
                
                foreach ($tasksOnDate as $taskId) {
                    if ($excess <= 0) break;
                    
                    $task = GanttTask::find($taskId);
                    if (!$task || !$this->canDelayTask($task)) {
                        continue;
                    }

                    // Find a valley day to move to
                    $valleyDate = $this->findValleyDate($profile['daily_usage'], $targetUsage, $date);
                    if (!$valleyDate) {
                        continue;
                    }

                    $delayDays = \Carbon\Carbon::parse($date)->diffInDays(\Carbon\Carbon::parse($valleyDate));
                    
                    if ($delayDays > 0 && $delayDays <= ($task->total_float ?? 0)) {
                        $newStartDate = \Carbon\Carbon::parse($task->start_date)->addDays($delayDays);
                        $newEndDate = \Carbon\Carbon::parse($task->end_date)->addDays($delayDays);

                        $smoothedTasks[$taskId] = [
                            'task' => $task,
                            'original_start' => $task->start_date,
                            'original_end' => $task->end_date,
                            'new_start' => $newStartDate->format('Y-m-d'),
                            'new_end' => $newEndDate->format('Y-m-d'),
                            'delay_days' => $delayDays,
                            'reason' => "Resource smoothing - Moving from peak to valley",
                        ];

                        $excess -= ($task->resources->first()?->units ?? 100);
                    }
                }
            }
        }

        return $smoothedTasks;
    }

    /**
     * Find a valley date for resource smoothing
     */
    protected function findValleyDate(array $dailyUsage, float $targetUsage, string $afterDate): ?string
    {
        ksort($dailyUsage);
        
        foreach ($dailyUsage as $date => $usage) {
            if ($date > $afterDate && $usage < $targetUsage) {
                return $date;
            }
        }

        return null;
    }

    /**
     * Calculate optimization results
     */
    protected function calculateOptimizationResults(array $optimizedTasks): void
    {
        $maxDelay = 0;
        $totalCost = 0;

        foreach ($optimizedTasks as $taskData) {
            $task = $taskData['task'];
            
            // Store detail
            ResourceOptimizationDetail::create([
                'resource_optimization_id' => $this->optimization->id,
                'gantt_task_id' => $task->id,
                'original_start_date' => $taskData['original_start'],
                'original_end_date' => $taskData['original_end'],
                'optimized_start_date' => $taskData['new_start'],
                'optimized_end_date' => $taskData['new_end'],
                'delay_days' => $taskData['delay_days'],
                'change_reason' => $taskData['reason'],
            ]);

            $maxDelay = max($maxDelay, $taskData['delay_days']);
        }

        // Calculate new project finish date
        $newFinishDate = $this->optimization->original_finish_date
            ? \Carbon\Carbon::parse($this->optimization->original_finish_date)->addDays($maxDelay)
            : null;

        // Calculate utilization
        $this->analyzeResourceUsage();
        $utilizationBefore = $this->calculateAverageUtilization();

        $this->optimization->update([
            'optimized_finish_date' => $newFinishDate,
            'resource_utilization_before' => $utilizationBefore,
            'resource_utilization_after' => $utilizationBefore, // Would need re-calculation after changes
            'optimization_results' => [
                'tasks_adjusted' => count($optimizedTasks),
                'max_delay_applied' => $maxDelay,
            ],
            'conflicts_resolved' => $this->conflicts,
        ]);
    }

    /**
     * Calculate average resource utilization
     */
    protected function calculateAverageUtilization(): float
    {
        $totalUtilization = 0;
        $count = 0;

        foreach ($this->resourceUsage as $resource => $dates) {
            foreach ($dates as $usage) {
                $totalUtilization += min(100, $usage['total_units']);
                $count++;
            }
        }

        return $count > 0 ? round($totalUtilization / $count, 2) : 0;
    }

    /**
     * Get resource utilization chart data
     */
    public function getUtilizationChart(int $projectId): array
    {
        $project = Project::with('ganttTasks.resources')->findOrFail($projectId);
        
        $this->optimization = new ResourceOptimization([
            'project_id' => $projectId,
        ]);
        $this->optimization->setRelation('project', $project);
        
        $this->analyzeResourceUsage();
        
        $chartData = [];
        foreach ($this->resourceUsage as $resourceName => $dates) {
            $chartData[$resourceName] = [
                'name' => $resourceName,
                'data' => array_map(fn($date, $usage) => [
                    'date' => $date,
                    'usage' => $usage['total_units'],
                    'overallocated' => $usage['total_units'] > 100,
                ], array_keys($dates), array_values($dates)),
            ];
        }

        return $chartData;
    }
}
