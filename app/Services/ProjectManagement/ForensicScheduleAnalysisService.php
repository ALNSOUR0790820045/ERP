<?php

namespace App\Services\ProjectManagement;

use App\Models\ForensicScheduleAnalysis;
use App\Models\ForensicDelayEvent;
use App\Models\Project;
use App\Models\GanttTask;
use Illuminate\Support\Facades\DB;

class ForensicScheduleAnalysisService
{
    protected ?ForensicScheduleAnalysis $analysis = null;

    /**
     * Create forensic schedule analysis
     */
    public function createAnalysis(int $projectId, array $data): ForensicScheduleAnalysis
    {
        $project = Project::findOrFail($projectId);

        $this->analysis = ForensicScheduleAnalysis::create([
            'project_id' => $projectId,
            'analysis_number' => ForensicScheduleAnalysis::generateNumber($projectId),
            'title' => $data['title'] ?? 'Forensic Schedule Analysis',
            'description' => $data['description'] ?? null,
            'analysis_type' => $data['analysis_type'] ?? 'delay',
            'methodology' => $data['methodology'] ?? 'as_planned_vs_as_built',
            'analysis_period_start' => $data['analysis_period_start'] ?? $project->start_date,
            'analysis_period_end' => $data['analysis_period_end'] ?? now(),
            'contract_completion_date' => $data['contract_completion_date'] ?? $project->end_date,
            'actual_completion_date' => $data['actual_completion_date'] ?? null,
            'extended_completion_date' => $data['extended_completion_date'] ?? null,
            'status' => 'draft',
            'analyst_id' => auth()->id(),
        ]);

        return $this->analysis;
    }

    /**
     * Add delay event to analysis
     */
    public function addDelayEvent(int $analysisId, array $data): ForensicDelayEvent
    {
        $analysis = ForensicScheduleAnalysis::findOrFail($analysisId);

        $event = ForensicDelayEvent::create([
            'forensic_schedule_analysis_id' => $analysisId,
            'event_id' => $data['event_id'] ?? 'EVT-' . uniqid(),
            'event_name' => $data['event_name'],
            'event_description' => $data['event_description'] ?? null,
            'responsible_party' => $data['responsible_party'],
            'delay_category' => $data['delay_category'],
            'event_start_date' => $data['event_start_date'],
            'event_end_date' => $data['event_end_date'],
            'gross_delay_days' => $data['gross_delay_days'] ?? 
                \Carbon\Carbon::parse($data['event_start_date'])
                    ->diffInDays(\Carbon\Carbon::parse($data['event_end_date'])),
            'concurrent_delay_days' => $data['concurrent_delay_days'] ?? 0,
            'net_delay_days' => $data['net_delay_days'] ?? ($data['gross_delay_days'] - ($data['concurrent_delay_days'] ?? 0)),
            'affected_activities' => $data['affected_activities'] ?? [],
            'critical_path_impact' => $data['critical_path_impact'] ?? false,
            'cost_impact' => $data['cost_impact'] ?? null,
            'supporting_documents' => $data['supporting_documents'] ?? null,
            'mitigation_efforts' => $data['mitigation_efforts'] ?? null,
        ]);

        // Recalculate totals
        $analysis->recalculateFromEvents();

        return $event;
    }

    /**
     * Run As-Planned vs As-Built analysis
     */
    public function runAsPlannedVsAsBuilt(int $analysisId): ForensicScheduleAnalysis
    {
        $this->analysis = ForensicScheduleAnalysis::with([
            'project.ganttTasks',
            'delayEvents',
        ])->findOrFail($analysisId);

        $this->analysis->update(['status' => 'in_progress']);

        try {
            $tasks = $this->analysis->project->ganttTasks;
            $criticalPathChanges = [];
            $floatConsumption = [];
            
            foreach ($tasks as $task) {
                // Compare planned vs actual dates
                $plannedStart = $task->planned_start ?? $task->start_date;
                $plannedEnd = $task->planned_end ?? $task->end_date;
                $actualStart = $task->actual_start;
                $actualEnd = $task->actual_end;

                if ($actualStart && $plannedStart) {
                    $startVariance = $this->daysBetween($plannedStart, $actualStart);
                    $endVariance = $actualEnd ? $this->daysBetween($plannedEnd, $actualEnd) : null;

                    if ($startVariance != 0 || ($endVariance && $endVariance != 0)) {
                        $criticalPathChanges[] = [
                            'task_id' => $task->id,
                            'task_name' => $task->name,
                            'planned_start' => $plannedStart?->format('Y-m-d'),
                            'actual_start' => $actualStart?->format('Y-m-d'),
                            'start_variance' => $startVariance,
                            'planned_end' => $plannedEnd?->format('Y-m-d'),
                            'actual_end' => $actualEnd?->format('Y-m-d'),
                            'end_variance' => $endVariance,
                            'was_critical' => $task->is_critical,
                        ];
                    }
                }

                // Track float consumption
                $originalFloat = $task->original_float ?? $task->total_float ?? 0;
                $currentFloat = $task->total_float ?? 0;
                $floatUsed = $originalFloat - $currentFloat;

                if ($floatUsed > 0) {
                    $floatConsumption[] = [
                        'task_id' => $task->id,
                        'task_name' => $task->name,
                        'original_float' => $originalFloat,
                        'current_float' => $currentFloat,
                        'float_consumed' => $floatUsed,
                    ];
                }
            }

            // Calculate total project delay
            $totalDelay = 0;
            if ($this->analysis->actual_completion_date && $this->analysis->contract_completion_date) {
                $totalDelay = $this->daysBetween(
                    $this->analysis->contract_completion_date,
                    $this->analysis->actual_completion_date
                );
            }

            $this->analysis->update([
                'total_delay_days' => max(0, $totalDelay),
                'critical_path_changes' => $criticalPathChanges,
                'float_consumption' => $floatConsumption,
                'status' => 'completed',
            ]);

        } catch (\Exception $e) {
            $this->analysis->update(['status' => 'draft']);
            throw $e;
        }

        return $this->analysis->fresh();
    }

    /**
     * Run Window Analysis
     */
    public function runWindowAnalysis(int $analysisId, int $windowDays = 30): ForensicScheduleAnalysis
    {
        $this->analysis = ForensicScheduleAnalysis::with([
            'project.ganttTasks',
            'delayEvents',
        ])->findOrFail($analysisId);

        $this->analysis->update(['status' => 'in_progress']);

        try {
            $periodStart = \Carbon\Carbon::parse($this->analysis->analysis_period_start);
            $periodEnd = \Carbon\Carbon::parse($this->analysis->analysis_period_end);
            $windows = [];
            $windowNumber = 1;

            // Divide analysis period into windows
            $windowStart = $periodStart->copy();
            while ($windowStart->lt($periodEnd)) {
                $windowEnd = $windowStart->copy()->addDays($windowDays);
                if ($windowEnd->gt($periodEnd)) {
                    $windowEnd = $periodEnd->copy();
                }

                // Analyze each window
                $windowAnalysis = $this->analyzeWindow(
                    $windowNumber,
                    $windowStart,
                    $windowEnd
                );

                $windows[] = $windowAnalysis;
                
                $windowStart = $windowEnd->copy();
                $windowNumber++;
            }

            // Summarize window results
            $totalContractorDelay = collect($windows)->sum('contractor_delay');
            $totalOwnerDelay = collect($windows)->sum('owner_delay');
            $totalConcurrentDelay = collect($windows)->sum('concurrent_delay');

            $this->analysis->update([
                'contractor_delay_days' => $totalContractorDelay,
                'owner_delay_days' => $totalOwnerDelay,
                'concurrent_delay_days' => $totalConcurrentDelay,
                'delay_events' => $windows,
                'status' => 'completed',
            ]);

        } catch (\Exception $e) {
            $this->analysis->update(['status' => 'draft']);
            throw $e;
        }

        return $this->analysis->fresh();
    }

    /**
     * Analyze a single window period
     */
    protected function analyzeWindow(int $number, $start, $end): array
    {
        // Find delay events in this window
        $windowEvents = $this->analysis->delayEvents->filter(function ($event) use ($start, $end) {
            $eventStart = \Carbon\Carbon::parse($event->event_start_date);
            $eventEnd = \Carbon\Carbon::parse($event->event_end_date);
            return $eventStart->between($start, $end) || $eventEnd->between($start, $end);
        });

        $contractorDelay = $windowEvents->where('responsible_party', 'contractor')->sum('net_delay_days');
        $ownerDelay = $windowEvents->where('responsible_party', 'owner')->sum('net_delay_days');
        $concurrentDelay = $windowEvents->sum('concurrent_delay_days');

        return [
            'window_number' => $number,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'contractor_delay' => $contractorDelay,
            'owner_delay' => $ownerDelay,
            'concurrent_delay' => $concurrentDelay,
            'events_count' => $windowEvents->count(),
            'events' => $windowEvents->pluck('event_name')->toArray(),
        ];
    }

    /**
     * Generate delay responsibility summary
     */
    public function generateResponsibilitySummary(int $analysisId): array
    {
        $analysis = ForensicScheduleAnalysis::with('delayEvents')->findOrFail($analysisId);

        $summary = [
            'contractor' => [
                'label' => 'المقاول',
                'events' => 0,
                'gross_days' => 0,
                'concurrent_days' => 0,
                'net_days' => 0,
                'cost_impact' => 0,
            ],
            'owner' => [
                'label' => 'المالك',
                'events' => 0,
                'gross_days' => 0,
                'concurrent_days' => 0,
                'net_days' => 0,
                'cost_impact' => 0,
            ],
            'third_party' => [
                'label' => 'طرف ثالث',
                'events' => 0,
                'gross_days' => 0,
                'concurrent_days' => 0,
                'net_days' => 0,
                'cost_impact' => 0,
            ],
            'force_majeure' => [
                'label' => 'قوة قاهرة',
                'events' => 0,
                'gross_days' => 0,
                'concurrent_days' => 0,
                'net_days' => 0,
                'cost_impact' => 0,
            ],
        ];

        foreach ($analysis->delayEvents as $event) {
            $party = $event->responsible_party;
            if (isset($summary[$party])) {
                $summary[$party]['events']++;
                $summary[$party]['gross_days'] += $event->gross_delay_days;
                $summary[$party]['concurrent_days'] += $event->concurrent_delay_days;
                $summary[$party]['net_days'] += $event->net_delay_days;
                $summary[$party]['cost_impact'] += $event->cost_impact ?? 0;
            }
        }

        // Calculate percentages
        $totalNetDays = array_sum(array_column($summary, 'net_days'));
        foreach ($summary as $party => $data) {
            $summary[$party]['percentage'] = $totalNetDays > 0 
                ? round(($data['net_days'] / $totalNetDays) * 100, 1) 
                : 0;
        }

        return $summary;
    }

    /**
     * Generate delay category summary
     */
    public function generateCategorySummary(int $analysisId): array
    {
        $analysis = ForensicScheduleAnalysis::with('delayEvents')->findOrFail($analysisId);

        $summary = [
            'excusable_compensable' => [
                'label' => 'معذور وقابل للتعويض',
                'days' => 0,
                'events' => 0,
                'entitled_eot' => true,
                'entitled_compensation' => true,
            ],
            'excusable_non_compensable' => [
                'label' => 'معذور غير قابل للتعويض',
                'days' => 0,
                'events' => 0,
                'entitled_eot' => true,
                'entitled_compensation' => false,
            ],
            'non_excusable' => [
                'label' => 'غير معذور',
                'days' => 0,
                'events' => 0,
                'entitled_eot' => false,
                'entitled_compensation' => false,
            ],
            'concurrent' => [
                'label' => 'متزامن',
                'days' => 0,
                'events' => 0,
                'entitled_eot' => 'depends',
                'entitled_compensation' => false,
            ],
        ];

        foreach ($analysis->delayEvents as $event) {
            $category = $event->delay_category;
            if (isset($summary[$category])) {
                $summary[$category]['days'] += $event->net_delay_days;
                $summary[$category]['events']++;
            }
        }

        return $summary;
    }

    /**
     * Generate findings and recommendations
     */
    public function generateFindings(int $analysisId): ForensicScheduleAnalysis
    {
        $analysis = ForensicScheduleAnalysis::with('delayEvents')->findOrFail($analysisId);

        $responsibilitySummary = $this->generateResponsibilitySummary($analysisId);
        $categorySummary = $this->generateCategorySummary($analysisId);

        $findings = "نتائج التحليل الجنائي للجدول الزمني\n";
        $findings .= "====================================\n\n";

        $findings .= "1. ملخص التأخير:\n";
        $findings .= "   - إجمالي التأخير: {$analysis->total_delay_days} يوم\n";
        $findings .= "   - تأخير المقاول: {$analysis->contractor_delay_days} يوم\n";
        $findings .= "   - تأخير المالك: {$analysis->owner_delay_days} يوم\n";
        $findings .= "   - تأخير متزامن: {$analysis->concurrent_delay_days} يوم\n\n";

        $findings .= "2. تحليل المسؤولية:\n";
        foreach ($responsibilitySummary as $party => $data) {
            $findings .= "   - {$data['label']}: {$data['net_days']} يوم ({$data['percentage']}%)\n";
        }
        $findings .= "\n";

        $findings .= "3. الاستحقاقات:\n";
        $findings .= "   - تمديد الوقت المستحق: {$categorySummary['excusable_compensable']['days']} + {$categorySummary['excusable_non_compensable']['days']} = " . 
            ($categorySummary['excusable_compensable']['days'] + $categorySummary['excusable_non_compensable']['days']) . " يوم\n";
        $findings .= "   - التعويض المالي المستحق: عن {$categorySummary['excusable_compensable']['days']} يوم تأخير قابل للتعويض\n";

        $recommendations = "التوصيات\n";
        $recommendations .= "=========\n\n";
        
        if ($analysis->excusable_delay_days > 0) {
            $recommendations .= "1. منح تمديد وقت بمقدار {$analysis->excusable_delay_days} يوم للتأخيرات المعذورة.\n";
        }
        
        if ($analysis->compensable_delay_days > 0) {
            $recommendations .= "2. النظر في التعويض المالي عن {$analysis->compensable_delay_days} يوم تأخير قابل للتعويض.\n";
        }
        
        if ($analysis->contractor_delay_days > 0) {
            $recommendations .= "3. تقييم تطبيق غرامات التأخير عن {$analysis->contractor_delay_days} يوم تأخير غير معذور من المقاول.\n";
        }

        $analysis->update([
            'findings' => $findings,
            'recommendations' => $recommendations,
        ]);

        return $analysis->fresh();
    }

    /**
     * Finalize analysis
     */
    public function finalizeAnalysis(int $analysisId, int $reviewerId): ForensicScheduleAnalysis
    {
        $analysis = ForensicScheduleAnalysis::findOrFail($analysisId);
        
        $analysis->update([
            'status' => 'final',
            'reviewer_id' => $reviewerId,
        ]);

        return $analysis->fresh();
    }

    protected function daysBetween($start, $end): int
    {
        return \Carbon\Carbon::parse($start)->diffInDays(\Carbon\Carbon::parse($end), false);
    }
}
