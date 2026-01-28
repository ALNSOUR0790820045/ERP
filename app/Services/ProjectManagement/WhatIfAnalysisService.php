<?php

namespace App\Services\ProjectManagement;

use App\Models\WhatIfScenario;
use App\Models\WhatIfScenarioChange;
use App\Models\Project;
use App\Models\GanttTask;
use Illuminate\Support\Facades\DB;

class WhatIfAnalysisService
{
    protected ?WhatIfScenario $scenario = null;

    /**
     * Create a new what-if scenario
     */
    public function createScenario(int $projectId, array $data): WhatIfScenario
    {
        $project = Project::findOrFail($projectId);

        $this->scenario = WhatIfScenario::create([
            'project_id' => $projectId,
            'name' => $data['name'] ?? 'What-If Scenario - ' . now()->format('Y-m-d'),
            'description' => $data['description'] ?? null,
            'scenario_type' => $data['scenario_type'] ?? 'combined',
            'status' => 'draft',
            'is_baseline' => $data['is_baseline'] ?? false,
            'baseline_start_date' => $project->start_date,
            'baseline_end_date' => $project->end_date,
            'baseline_cost' => $project->budget ?? $project->ganttTasks->sum('budgeted_cost'),
            'scenario_start_date' => $project->start_date,
            'scenario_end_date' => $project->end_date,
            'scenario_cost' => $project->budget ?? $project->ganttTasks->sum('budgeted_cost'),
            'assumptions' => $data['assumptions'] ?? [],
            'created_by' => auth()->id(),
        ]);

        return $this->scenario;
    }

    /**
     * Create baseline scenario from current project state
     */
    public function createBaseline(int $projectId): WhatIfScenario
    {
        $project = Project::with('ganttTasks')->findOrFail($projectId);

        // Deactivate existing baselines
        WhatIfScenario::where('project_id', $projectId)
            ->where('is_baseline', true)
            ->update(['is_baseline' => false, 'status' => 'archived']);

        $this->scenario = WhatIfScenario::create([
            'project_id' => $projectId,
            'name' => 'Baseline - ' . now()->format('Y-m-d'),
            'description' => 'Project baseline captured on ' . now()->format('Y-m-d H:i'),
            'scenario_type' => 'combined',
            'status' => 'active',
            'is_baseline' => true,
            'baseline_start_date' => $project->start_date,
            'baseline_end_date' => $project->end_date,
            'baseline_cost' => $project->budget ?? $project->ganttTasks->sum('budgeted_cost'),
            'scenario_start_date' => $project->start_date,
            'scenario_end_date' => $project->end_date,
            'scenario_cost' => $project->budget ?? $project->ganttTasks->sum('budgeted_cost'),
            'created_by' => auth()->id(),
        ]);

        return $this->scenario;
    }

    /**
     * Add a change to a scenario
     */
    public function addChange(int $scenarioId, array $data): WhatIfScenarioChange
    {
        $scenario = WhatIfScenario::findOrFail($scenarioId);
        
        $change = WhatIfScenarioChange::create([
            'what_if_scenario_id' => $scenarioId,
            'gantt_task_id' => $data['gantt_task_id'] ?? null,
            'project_resource_id' => $data['project_resource_id'] ?? null,
            'change_type' => $data['change_type'],
            'field_name' => $data['field_name'],
            'original_value' => $data['original_value'] ?? null,
            'new_value' => $data['new_value'],
            'reason' => $data['reason'] ?? null,
            'impact_days' => $data['impact_days'] ?? null,
            'impact_cost' => $data['impact_cost'] ?? null,
        ]);

        // Recalculate scenario
        $this->recalculateScenario($scenarioId);

        return $change;
    }

    /**
     * Add duration change to task
     */
    public function changeDuration(int $scenarioId, int $taskId, int $newDuration, ?string $reason = null): WhatIfScenarioChange
    {
        $task = GanttTask::findOrFail($taskId);
        $originalDuration = $task->duration ?? 0;
        $durationDiff = $newDuration - $originalDuration;

        return $this->addChange($scenarioId, [
            'gantt_task_id' => $taskId,
            'change_type' => 'duration',
            'field_name' => 'duration',
            'original_value' => (string) $originalDuration,
            'new_value' => (string) $newDuration,
            'reason' => $reason,
            'impact_days' => $durationDiff,
        ]);
    }

    /**
     * Add date change to task
     */
    public function changeStartDate(int $scenarioId, int $taskId, string $newDate, ?string $reason = null): WhatIfScenarioChange
    {
        $task = GanttTask::findOrFail($taskId);
        $originalDate = $task->start_date?->format('Y-m-d');
        $dateDiff = $originalDate 
            ? \Carbon\Carbon::parse($newDate)->diffInDays(\Carbon\Carbon::parse($originalDate), false)
            : 0;

        return $this->addChange($scenarioId, [
            'gantt_task_id' => $taskId,
            'change_type' => 'start_date',
            'field_name' => 'start_date',
            'original_value' => $originalDate,
            'new_value' => $newDate,
            'reason' => $reason,
            'impact_days' => $dateDiff,
        ]);
    }

    /**
     * Add cost change to task
     */
    public function changeCost(int $scenarioId, int $taskId, float $newCost, ?string $reason = null): WhatIfScenarioChange
    {
        $task = GanttTask::findOrFail($taskId);
        $originalCost = $task->budgeted_cost ?? 0;
        $costDiff = $newCost - $originalCost;

        return $this->addChange($scenarioId, [
            'gantt_task_id' => $taskId,
            'change_type' => 'cost',
            'field_name' => 'budgeted_cost',
            'original_value' => (string) $originalCost,
            'new_value' => (string) $newCost,
            'reason' => $reason,
            'impact_cost' => $costDiff,
        ]);
    }

    /**
     * Add new task to scenario
     */
    public function addTask(int $scenarioId, array $taskData, ?string $reason = null): WhatIfScenarioChange
    {
        return $this->addChange($scenarioId, [
            'change_type' => 'add_task',
            'field_name' => 'task',
            'new_value' => json_encode($taskData),
            'reason' => $reason,
            'impact_days' => $taskData['duration'] ?? 0,
            'impact_cost' => $taskData['cost'] ?? 0,
        ]);
    }

    /**
     * Remove task from scenario
     */
    public function removeTask(int $scenarioId, int $taskId, ?string $reason = null): WhatIfScenarioChange
    {
        $task = GanttTask::findOrFail($taskId);

        return $this->addChange($scenarioId, [
            'gantt_task_id' => $taskId,
            'change_type' => 'remove_task',
            'field_name' => 'task',
            'original_value' => json_encode([
                'name' => $task->name,
                'duration' => $task->duration,
                'cost' => $task->budgeted_cost,
            ]),
            'reason' => $reason,
            'impact_days' => -($task->duration ?? 0),
            'impact_cost' => -($task->budgeted_cost ?? 0),
        ]);
    }

    /**
     * Recalculate scenario impact
     */
    public function recalculateScenario(int $scenarioId): WhatIfScenario
    {
        $scenario = WhatIfScenario::with(['changes', 'project.ganttTasks'])->findOrFail($scenarioId);
        
        // Calculate total impact
        $totalDaysImpact = $scenario->changes->sum('impact_days');
        $totalCostImpact = $scenario->changes->sum('impact_cost');

        // Calculate new scenario dates and cost
        $scenarioEndDate = $scenario->baseline_end_date
            ? \Carbon\Carbon::parse($scenario->baseline_end_date)->addDays($totalDaysImpact)
            : null;
        
        $scenarioCost = ($scenario->baseline_cost ?? 0) + $totalCostImpact;

        $scenario->update([
            'scenario_end_date' => $scenarioEndDate,
            'scenario_cost' => $scenarioCost,
            'schedule_variance_days' => $totalDaysImpact,
            'cost_variance' => $totalCostImpact,
            'schedule_variance_percent' => $this->calculateScheduleVariancePercent($scenario, $totalDaysImpact),
            'cost_variance_percent' => $this->calculateCostVariancePercent($scenario, $totalCostImpact),
            'impact_summary' => $this->generateImpactSummary($scenario),
        ]);

        return $scenario->fresh();
    }

    /**
     * Compare two scenarios
     */
    public function compareScenarios(int $scenarioId1, int $scenarioId2): array
    {
        $scenario1 = WhatIfScenario::with('changes')->findOrFail($scenarioId1);
        $scenario2 = WhatIfScenario::with('changes')->findOrFail($scenarioId2);

        $dateDiff = 0;
        if ($scenario1->scenario_end_date && $scenario2->scenario_end_date) {
            $dateDiff = \Carbon\Carbon::parse($scenario1->scenario_end_date)
                ->diffInDays(\Carbon\Carbon::parse($scenario2->scenario_end_date), false);
        }

        return [
            'scenario1' => [
                'id' => $scenario1->id,
                'name' => $scenario1->name,
                'end_date' => $scenario1->scenario_end_date?->format('Y-m-d'),
                'cost' => $scenario1->scenario_cost,
                'changes_count' => $scenario1->changes->count(),
            ],
            'scenario2' => [
                'id' => $scenario2->id,
                'name' => $scenario2->name,
                'end_date' => $scenario2->scenario_end_date?->format('Y-m-d'),
                'cost' => $scenario2->scenario_cost,
                'changes_count' => $scenario2->changes->count(),
            ],
            'comparison' => [
                'schedule_difference_days' => $dateDiff,
                'cost_difference' => ($scenario1->scenario_cost ?? 0) - ($scenario2->scenario_cost ?? 0),
                'schedule_winner' => $dateDiff > 0 ? $scenario2->name : $scenario1->name,
                'cost_winner' => $scenario1->scenario_cost < $scenario2->scenario_cost 
                    ? $scenario1->name 
                    : $scenario2->name,
            ],
        ];
    }

    /**
     * Apply scenario changes to project
     */
    public function applyScenario(int $scenarioId): void
    {
        $scenario = WhatIfScenario::with('changes')->findOrFail($scenarioId);

        DB::beginTransaction();

        try {
            foreach ($scenario->changes as $change) {
                if (!$change->gantt_task_id) {
                    continue;
                }

                $task = GanttTask::find($change->gantt_task_id);
                if (!$task) {
                    continue;
                }

                switch ($change->change_type) {
                    case 'duration':
                        $task->update(['duration' => (int) $change->new_value]);
                        break;
                    case 'start_date':
                        $task->update(['start_date' => $change->new_value]);
                        break;
                    case 'end_date':
                        $task->update(['end_date' => $change->new_value]);
                        break;
                    case 'cost':
                        $task->update(['budgeted_cost' => (float) $change->new_value]);
                        break;
                    case 'remove_task':
                        $task->delete();
                        break;
                }
            }

            $scenario->update(['status' => 'applied']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Clone scenario
     */
    public function cloneScenario(int $scenarioId, ?string $newName = null): WhatIfScenario
    {
        $original = WhatIfScenario::with('changes')->findOrFail($scenarioId);

        $clone = $original->replicate();
        $clone->name = $newName ?? $original->name . ' (Copy)';
        $clone->status = 'draft';
        $clone->is_baseline = false;
        $clone->created_by = auth()->id();
        $clone->approved_by = null;
        $clone->approved_at = null;
        $clone->save();

        // Clone changes
        foreach ($original->changes as $change) {
            $changeClone = $change->replicate();
            $changeClone->what_if_scenario_id = $clone->id;
            $changeClone->save();
        }

        return $clone;
    }

    protected function calculateScheduleVariancePercent(WhatIfScenario $scenario, int $daysImpact): ?float
    {
        if (!$scenario->baseline_start_date || !$scenario->baseline_end_date) {
            return null;
        }

        $baselineDuration = \Carbon\Carbon::parse($scenario->baseline_start_date)
            ->diffInDays(\Carbon\Carbon::parse($scenario->baseline_end_date));

        if ($baselineDuration == 0) {
            return null;
        }

        return round(($daysImpact / $baselineDuration) * 100, 2);
    }

    protected function calculateCostVariancePercent(WhatIfScenario $scenario, float $costImpact): ?float
    {
        if (!$scenario->baseline_cost || $scenario->baseline_cost == 0) {
            return null;
        }

        return round(($costImpact / $scenario->baseline_cost) * 100, 2);
    }

    protected function generateImpactSummary(WhatIfScenario $scenario): array
    {
        $changes = $scenario->changes;

        return [
            'total_changes' => $changes->count(),
            'duration_changes' => $changes->where('change_type', 'duration')->count(),
            'date_changes' => $changes->whereIn('change_type', ['start_date', 'end_date'])->count(),
            'cost_changes' => $changes->where('change_type', 'cost')->count(),
            'task_additions' => $changes->where('change_type', 'add_task')->count(),
            'task_removals' => $changes->where('change_type', 'remove_task')->count(),
            'net_schedule_impact_days' => $changes->sum('impact_days'),
            'net_cost_impact' => $changes->sum('impact_cost'),
            'affected_tasks' => $changes->pluck('gantt_task_id')->filter()->unique()->count(),
        ];
    }
}
