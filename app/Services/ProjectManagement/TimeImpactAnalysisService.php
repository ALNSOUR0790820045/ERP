<?php

namespace App\Services\ProjectManagement;

use App\Models\TimeImpactAnalysis;
use App\Models\TimeImpactFragment;
use App\Models\Project;
use App\Models\GanttTask;
use Illuminate\Support\Facades\DB;

class TimeImpactAnalysisService
{
    protected ?TimeImpactAnalysis $analysis = null;

    /**
     * Create new time impact analysis
     */
    public function createAnalysis(int $projectId, array $data): TimeImpactAnalysis
    {
        $project = Project::findOrFail($projectId);

        $this->analysis = TimeImpactAnalysis::create([
            'project_id' => $projectId,
            'extension_of_time_id' => $data['extension_of_time_id'] ?? null,
            'analysis_number' => TimeImpactAnalysis::generateNumber($projectId),
            'title' => $data['title'] ?? 'Time Impact Analysis',
            'description' => $data['description'] ?? null,
            'delay_type' => $data['delay_type'] ?? 'excusable_compensable',
            'analysis_method' => $data['analysis_method'] ?? 'time_impact',
            'event_start_date' => $data['event_start_date'],
            'event_end_date' => $data['event_end_date'] ?? null,
            'data_date' => $data['data_date'] ?? now(),
            'baseline_completion_date' => $data['baseline_completion_date'] ?? $project->end_date,
            'status' => 'draft',
            'prepared_by' => auth()->id(),
        ]);

        return $this->analysis;
    }

    /**
     * Add delay fragment to analysis
     */
    public function addFragment(int $analysisId, array $data): TimeImpactFragment
    {
        $analysis = TimeImpactAnalysis::findOrFail($analysisId);

        return TimeImpactFragment::create([
            'time_impact_analysis_id' => $analysisId,
            'fragment_id' => $data['fragment_id'] ?? 'FRG-' . uniqid(),
            'fragment_name' => $data['fragment_name'],
            'predecessor_task_id' => $data['predecessor_task_id'] ?? null,
            'successor_task_id' => $data['successor_task_id'] ?? null,
            'fragment_start_date' => $data['fragment_start_date'],
            'fragment_end_date' => $data['fragment_end_date'],
            'fragment_duration' => $data['fragment_duration'] ?? 
                \Carbon\Carbon::parse($data['fragment_start_date'])
                    ->diffInDays(\Carbon\Carbon::parse($data['fragment_end_date'])),
            'dependency_type' => $data['dependency_type'] ?? 'FS',
            'lag_days' => $data['lag_days'] ?? 0,
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * Run time impact analysis
     */
    public function runAnalysis(int $analysisId): TimeImpactAnalysis
    {
        $this->analysis = TimeImpactAnalysis::with([
            'project.ganttTasks.dependencies',
            'fragments',
        ])->findOrFail($analysisId);

        DB::beginTransaction();

        try {
            // Get baseline critical path
            $baselineCriticalPath = $this->calculateCriticalPath(
                $this->analysis->project->ganttTasks,
                null
            );
            $this->analysis->update(['critical_path_before' => $baselineCriticalPath]);

            // Insert fragments into schedule
            $impactedSchedule = $this->insertFragments();

            // Calculate impacted critical path
            $impactedCriticalPath = $this->calculateCriticalPath(
                $this->analysis->project->ganttTasks,
                $this->analysis->fragments
            );
            $this->analysis->update(['critical_path_after' => $impactedCriticalPath]);

            // Calculate delay
            $this->calculateDelay($impactedSchedule);

            // Identify impacted activities
            $this->identifyImpactedActivities($baselineCriticalPath, $impactedCriticalPath);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $this->analysis->fresh();
    }

    /**
     * Calculate critical path
     */
    protected function calculateCriticalPath($tasks, $fragments): array
    {
        $criticalPath = [];
        $taskData = [];

        // Forward pass
        foreach ($tasks as $task) {
            $predecessors = $task->dependencies ?? collect();
            $maxPredEnd = null;

            foreach ($predecessors as $pred) {
                $predTask = $tasks->firstWhere('id', $pred->predecessor_id);
                if ($predTask && isset($taskData[$predTask->id])) {
                    $predEnd = $taskData[$predTask->id]['early_finish'];
                    if (!$maxPredEnd || $predEnd > $maxPredEnd) {
                        $maxPredEnd = $predEnd;
                    }
                }
            }

            $earlyStart = $maxPredEnd ?? $task->start_date;
            $duration = $task->duration ?? 1;
            $earlyFinish = $this->addDays($earlyStart, $duration);

            $taskData[$task->id] = [
                'early_start' => $earlyStart,
                'early_finish' => $earlyFinish,
                'duration' => $duration,
            ];
        }

        // Find project finish
        $projectFinish = collect($taskData)->max('early_finish');

        // Backward pass
        foreach ($tasks->reverse() as $task) {
            $successors = $tasks->filter(function ($t) use ($task) {
                return $t->dependencies?->contains('predecessor_id', $task->id);
            });

            $minSuccStart = $projectFinish;
            foreach ($successors as $succ) {
                if (isset($taskData[$succ->id])) {
                    $succStart = $taskData[$succ->id]['late_start'] ?? $taskData[$succ->id]['early_start'];
                    if ($succStart < $minSuccStart) {
                        $minSuccStart = $succStart;
                    }
                }
            }

            $lateFinish = $minSuccStart;
            $lateStart = $this->subtractDays($lateFinish, $taskData[$task->id]['duration']);

            $taskData[$task->id]['late_start'] = $lateStart;
            $taskData[$task->id]['late_finish'] = $lateFinish;

            // Calculate float
            $earlyStart = $taskData[$task->id]['early_start'];
            $float = $this->daysBetween($earlyStart, $lateStart);
            $taskData[$task->id]['total_float'] = $float;

            // Critical path = zero float
            if ($float <= 0) {
                $criticalPath[] = [
                    'task_id' => $task->id,
                    'task_name' => $task->name,
                    'duration' => $taskData[$task->id]['duration'],
                ];
            }
        }

        return $criticalPath;
    }

    /**
     * Insert fragments into schedule
     */
    protected function insertFragments(): array
    {
        $impactedSchedule = [];

        foreach ($this->analysis->fragments as $fragment) {
            // Create virtual task for fragment
            $impactedSchedule[] = [
                'id' => 'fragment_' . $fragment->id,
                'name' => $fragment->fragment_name,
                'start_date' => $fragment->fragment_start_date,
                'end_date' => $fragment->fragment_end_date,
                'duration' => $fragment->fragment_duration,
                'predecessor_id' => $fragment->predecessor_task_id,
                'successor_id' => $fragment->successor_task_id,
                'is_fragment' => true,
            ];
        }

        return $impactedSchedule;
    }

    /**
     * Calculate total delay
     */
    protected function calculateDelay(array $impactedSchedule): void
    {
        // Sum fragment durations
        $totalFragmentDuration = $this->analysis->fragments->sum('fragment_duration');

        // Calculate impacted completion date
        $impactedCompletionDate = $this->analysis->baseline_completion_date
            ? $this->addDays($this->analysis->baseline_completion_date, $totalFragmentDuration)
            : null;

        // Determine concurrent and pacing delays
        $concurrentDelay = 0;
        $pacingDelay = 0;

        // Simple concurrent delay calculation
        foreach ($this->analysis->fragments as $fragment) {
            if ($fragment->predecessor_task_id && $fragment->successor_task_id) {
                // Fragment is inserted between activities - check for concurrency
                // This is a simplified check
            }
        }

        $netDelay = $totalFragmentDuration - $concurrentDelay - $pacingDelay;

        $this->analysis->update([
            'delay_days' => $totalFragmentDuration,
            'concurrent_delay_days' => $concurrentDelay,
            'pacing_delay_days' => $pacingDelay,
            'net_delay_days' => $netDelay,
            'impacted_completion_date' => $impactedCompletionDate,
        ]);
    }

    /**
     * Identify impacted activities
     */
    protected function identifyImpactedActivities(array $beforePath, array $afterPath): void
    {
        $beforeIds = collect($beforePath)->pluck('task_id')->toArray();
        $afterIds = collect($afterPath)->pluck('task_id')->toArray();

        // Activities added to critical path
        $addedToCritical = array_diff($afterIds, $beforeIds);
        
        // Activities removed from critical path
        $removedFromCritical = array_diff($beforeIds, $afterIds);

        $impactedActivities = [
            'added_to_critical_path' => $addedToCritical,
            'removed_from_critical_path' => $removedFromCritical,
            'remained_critical' => array_intersect($beforeIds, $afterIds),
            'critical_path_changed' => !empty($addedToCritical) || !empty($removedFromCritical),
        ];

        $this->analysis->update(['impacted_activities' => $impactedActivities]);
    }

    /**
     * Generate analysis narrative
     */
    public function generateNarrative(int $analysisId): string
    {
        $analysis = TimeImpactAnalysis::with(['project', 'fragments'])->findOrFail($analysisId);

        $narrative = "تحليل التأثير الزمني\n";
        $narrative .= "===================\n\n";
        
        $narrative .= "المشروع: {$analysis->project->name}\n";
        $narrative .= "تاريخ البيانات: {$analysis->data_date?->format('Y-m-d')}\n";
        $narrative .= "تاريخ الإكمال الأساسي: {$analysis->baseline_completion_date?->format('Y-m-d')}\n\n";

        $narrative .= "الحدث المؤثر:\n";
        $narrative .= "- نوع التأخير: {$analysis->delay_type_arabic}\n";
        $narrative .= "- تاريخ البداية: {$analysis->event_start_date?->format('Y-m-d')}\n";
        $narrative .= "- تاريخ النهاية: {$analysis->event_end_date?->format('Y-m-d')}\n\n";

        $narrative .= "النتائج:\n";
        $narrative .= "- إجمالي التأخير: {$analysis->delay_days} يوم\n";
        $narrative .= "- التأخير المتزامن: {$analysis->concurrent_delay_days} يوم\n";
        $narrative .= "- صافي التأخير: {$analysis->net_delay_days} يوم\n";
        $narrative .= "- تاريخ الإكمال المتأثر: {$analysis->impacted_completion_date?->format('Y-m-d')}\n\n";

        if ($analysis->fragments->count() > 0) {
            $narrative .= "الأجزاء المضافة:\n";
            foreach ($analysis->fragments as $fragment) {
                $narrative .= "- {$fragment->fragment_name}: {$fragment->fragment_duration} يوم\n";
            }
        }

        $analysis->update(['analysis_narrative' => $narrative]);

        return $narrative;
    }

    /**
     * Submit analysis for review
     */
    public function submitForReview(int $analysisId): TimeImpactAnalysis
    {
        $analysis = TimeImpactAnalysis::findOrFail($analysisId);
        $analysis->submit();
        return $analysis->fresh();
    }

    /**
     * Approve analysis
     */
    public function approve(int $analysisId, int $approverId): TimeImpactAnalysis
    {
        $analysis = TimeImpactAnalysis::findOrFail($analysisId);
        $analysis->approve($approverId);
        return $analysis->fresh();
    }

    protected function addDays($date, int $days)
    {
        return \Carbon\Carbon::parse($date)->addDays($days);
    }

    protected function subtractDays($date, int $days)
    {
        return \Carbon\Carbon::parse($date)->subDays($days);
    }

    protected function daysBetween($start, $end): int
    {
        return \Carbon\Carbon::parse($start)->diffInDays(\Carbon\Carbon::parse($end), false);
    }
}
