<?php

namespace App\Services\ProjectManagement;

use App\Models\BimModel;
use App\Models\BimElementLink;
use App\Models\Project;
use App\Models\GanttTask;
use App\Models\BoqItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class BimIntegrationService
{
    protected ?BimModel $model = null;

    /**
     * Upload and process BIM model
     */
    public function uploadModel(int $projectId, UploadedFile $file, array $data = []): BimModel
    {
        $project = Project::findOrFail($projectId);

        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('bim/models', $fileName, 'public');
        $fileFormat = strtolower($file->getClientOriginalExtension());

        $this->model = BimModel::create([
            'project_id' => $projectId,
            'name' => $data['name'] ?? $file->getClientOriginalName(),
            'description' => $data['description'] ?? null,
            'model_type' => $data['model_type'] ?? 'combined',
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'file_format' => $fileFormat,
            'software_name' => $data['software_name'] ?? null,
            'software_version' => $data['software_version'] ?? null,
            'lod' => $data['lod'] ?? null,
            'is_active' => true,
            'uploaded_by' => auth()->id(),
        ]);

        // Process IFC file if applicable
        if ($fileFormat === 'ifc') {
            $this->processIfcFile($filePath);
        }

        return $this->model;
    }

    /**
     * Process IFC file and extract elements
     */
    protected function processIfcFile(string $filePath): void
    {
        $fullPath = Storage::disk('public')->path($filePath);
        
        if (!file_exists($fullPath)) {
            return;
        }

        $content = file_get_contents($fullPath);
        $elements = [];

        // Extract IFC schema version
        if (preg_match('/FILE_SCHEMA\s*\(\s*\(\s*\'(IFC\w+)\'\s*\)\s*\)/i', $content, $matches)) {
            $this->model->update(['ifc_schema_version' => $matches[1]]);
        }

        // Parse IFC elements (simplified parsing)
        // In production, use a proper IFC parser library
        $patterns = [
            'IFCBUILDINGELEMENTPROXY' => 'proxy',
            'IFCWALL' => 'wall',
            'IFCWALLSTANDARDCASE' => 'wall',
            'IFCSLAB' => 'slab',
            'IFCCOLUMN' => 'column',
            'IFCBEAM' => 'beam',
            'IFCDOOR' => 'door',
            'IFCWINDOW' => 'window',
            'IFCROOF' => 'roof',
            'IFCSTAIR' => 'stair',
            'IFCRAILING' => 'railing',
            'IFCCOVERING' => 'covering',
            'IFCFURNISHINGELEMENT' => 'furniture',
            'IFCFLOWSEGMENT' => 'mep_segment',
            'IFCFLOWTERMINAL' => 'mep_terminal',
        ];

        foreach ($patterns as $ifcClass => $elementType) {
            preg_match_all(
                "/#(\d+)\s*=\s*{$ifcClass}\s*\(\s*'([^']*)'/i",
                $content,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                $elements[] = [
                    'guid' => $match[2],
                    'ifc_class' => $ifcClass,
                    'element_type' => $elementType,
                ];
            }
        }

        // Create element links
        foreach ($elements as $element) {
            BimElementLink::create([
                'bim_model_id' => $this->model->id,
                'element_guid' => $element['guid'],
                'element_type' => $element['element_type'],
                'ifc_class' => $element['ifc_class'],
                'status' => 'not_started',
                'progress_percent' => 0,
            ]);
        }

        $this->model->update(['elements_count' => count($elements)]);
    }

    /**
     * Link BIM element to schedule task (4D)
     */
    public function linkToTask(int $elementLinkId, int $taskId): BimElementLink
    {
        $link = BimElementLink::findOrFail($elementLinkId);
        $task = GanttTask::findOrFail($taskId);

        $link->update([
            'gantt_task_id' => $taskId,
        ]);

        return $link->fresh();
    }

    /**
     * Link BIM element to BOQ item (5D)
     */
    public function linkToBoq(int $elementLinkId, int $boqItemId): BimElementLink
    {
        $link = BimElementLink::findOrFail($elementLinkId);
        $boqItem = BoqItem::findOrFail($boqItemId);

        $link->update([
            'boq_item_id' => $boqItemId,
            'unit_cost' => $boqItem->unit_price ?? $boqItem->rate,
        ]);

        // Calculate total cost if quantity exists
        if ($link->quantity && $link->unit_cost) {
            $link->calculateTotalCost();
        }

        return $link->fresh();
    }

    /**
     * Bulk link elements to task
     */
    public function bulkLinkToTask(array $elementLinkIds, int $taskId): int
    {
        return BimElementLink::whereIn('id', $elementLinkIds)
            ->update(['gantt_task_id' => $taskId]);
    }

    /**
     * Bulk link elements to BOQ
     */
    public function bulkLinkToBoq(array $elementLinkIds, int $boqItemId): int
    {
        $boqItem = BoqItem::findOrFail($boqItemId);
        
        return BimElementLink::whereIn('id', $elementLinkIds)
            ->update([
                'boq_item_id' => $boqItemId,
                'unit_cost' => $boqItem->unit_price ?? $boqItem->rate,
            ]);
    }

    /**
     * Update element progress from linked task
     */
    public function syncProgressFromTasks(int $modelId): void
    {
        $links = BimElementLink::where('bim_model_id', $modelId)
            ->whereNotNull('gantt_task_id')
            ->with('ganttTask')
            ->get();

        foreach ($links as $link) {
            if ($link->ganttTask) {
                $link->updateProgress($link->ganttTask->progress ?? 0);
            }
        }
    }

    /**
     * Get 4D simulation data (elements grouped by schedule)
     */
    public function get4DSimulationData(int $modelId): array
    {
        $links = BimElementLink::where('bim_model_id', $modelId)
            ->whereNotNull('gantt_task_id')
            ->with(['ganttTask'])
            ->get();

        $timeline = [];
        
        foreach ($links->groupBy('gantt_task_id') as $taskId => $elements) {
            $task = $elements->first()->ganttTask;
            if (!$task) continue;

            $timeline[] = [
                'task_id' => $taskId,
                'task_name' => $task->name,
                'start_date' => $task->start_date?->format('Y-m-d'),
                'end_date' => $task->end_date?->format('Y-m-d'),
                'progress' => $task->progress ?? 0,
                'elements' => $elements->pluck('element_guid')->toArray(),
                'element_count' => $elements->count(),
            ];
        }

        // Sort by start date
        usort($timeline, fn($a, $b) => strcmp($a['start_date'] ?? '', $b['start_date'] ?? ''));

        return $timeline;
    }

    /**
     * Get 5D cost data (elements with cost information)
     */
    public function get5DCostData(int $modelId): array
    {
        $links = BimElementLink::where('bim_model_id', $modelId)
            ->whereNotNull('total_cost')
            ->orWhereNotNull('boq_item_id')
            ->with(['boqItem', 'ganttTask'])
            ->get();

        $costData = [
            'total_cost' => 0,
            'by_element_type' => [],
            'by_task' => [],
            'elements' => [],
        ];

        foreach ($links as $link) {
            $cost = $link->total_cost ?? ($link->quantity * $link->unit_cost) ?? 0;
            $costData['total_cost'] += $cost;

            // Group by element type
            $type = $link->element_type ?? 'unknown';
            if (!isset($costData['by_element_type'][$type])) {
                $costData['by_element_type'][$type] = 0;
            }
            $costData['by_element_type'][$type] += $cost;

            // Group by task
            if ($link->gantt_task_id) {
                $taskName = $link->ganttTask?->name ?? 'Unknown Task';
                if (!isset($costData['by_task'][$taskName])) {
                    $costData['by_task'][$taskName] = 0;
                }
                $costData['by_task'][$taskName] += $cost;
            }

            $costData['elements'][] = [
                'guid' => $link->element_guid,
                'name' => $link->element_name,
                'type' => $type,
                'quantity' => $link->quantity,
                'unit' => $link->quantity_unit,
                'unit_cost' => $link->unit_cost,
                'total_cost' => $cost,
            ];
        }

        return $costData;
    }

    /**
     * Get element statistics by IFC class
     */
    public function getElementStatistics(int $modelId): array
    {
        $stats = BimElementLink::where('bim_model_id', $modelId)
            ->selectRaw('ifc_class, COUNT(*) as count, 
                         SUM(CASE WHEN gantt_task_id IS NOT NULL THEN 1 ELSE 0 END) as linked_to_schedule,
                         SUM(CASE WHEN boq_item_id IS NOT NULL THEN 1 ELSE 0 END) as linked_to_boq,
                         AVG(progress_percent) as avg_progress')
            ->groupBy('ifc_class')
            ->get();

        return $stats->toArray();
    }

    /**
     * Get progress visualization data
     */
    public function getProgressVisualization(int $modelId, ?string $date = null): array
    {
        $targetDate = $date ? \Carbon\Carbon::parse($date) : now();
        
        $links = BimElementLink::where('bim_model_id', $modelId)
            ->with('ganttTask')
            ->get();

        $visualization = [
            'date' => $targetDate->format('Y-m-d'),
            'elements' => [],
            'summary' => [
                'not_started' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'not_linked' => 0,
            ],
        ];

        foreach ($links as $link) {
            $status = 'not_linked';
            $progress = 0;

            if ($link->ganttTask) {
                // Check if task should have started by target date
                $taskStart = $link->ganttTask->start_date;
                $taskEnd = $link->ganttTask->end_date;

                if ($taskEnd && $targetDate->gte($taskEnd)) {
                    // Task should be complete
                    $status = 'completed';
                    $progress = 100;
                } elseif ($taskStart && $targetDate->gte($taskStart)) {
                    // Task should be in progress
                    $status = 'in_progress';
                    // Calculate expected progress based on date
                    if ($taskStart && $taskEnd) {
                        $totalDays = $taskStart->diffInDays($taskEnd);
                        $elapsedDays = $taskStart->diffInDays($targetDate);
                        $progress = min(100, ($elapsedDays / max(1, $totalDays)) * 100);
                    }
                } else {
                    $status = 'not_started';
                    $progress = 0;
                }
            }

            $visualization['elements'][] = [
                'guid' => $link->element_guid,
                'status' => $status,
                'progress' => round($progress, 2),
                'color' => $this->getProgressColor($status, $progress),
            ];

            $visualization['summary'][$status]++;
        }

        return $visualization;
    }

    /**
     * Get color for progress visualization
     */
    protected function getProgressColor(string $status, float $progress): string
    {
        return match ($status) {
            'completed' => '#22c55e', // Green
            'in_progress' => $this->interpolateColor($progress), // Yellow to Green
            'not_started' => '#94a3b8', // Gray
            'not_linked' => '#e2e8f0', // Light gray
            default => '#e2e8f0',
        };
    }

    /**
     * Interpolate color based on progress
     */
    protected function interpolateColor(float $progress): string
    {
        // From yellow (#eab308) to green (#22c55e)
        $startR = 234; $startG = 179; $startB = 8;
        $endR = 34; $endG = 197; $endB = 94;
        
        $factor = $progress / 100;
        
        $r = round($startR + ($endR - $startR) * $factor);
        $g = round($startG + ($endG - $startG) * $factor);
        $b = round($startB + ($endB - $startB) * $factor);
        
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Export linked data for external 4D/5D software
     */
    public function exportLinkingData(int $modelId, string $format = 'csv'): string
    {
        $links = BimElementLink::where('bim_model_id', $modelId)
            ->with(['ganttTask', 'boqItem'])
            ->get();

        $data = [];
        foreach ($links as $link) {
            $data[] = [
                'element_guid' => $link->element_guid,
                'element_name' => $link->element_name,
                'element_type' => $link->element_type,
                'ifc_class' => $link->ifc_class,
                'task_code' => $link->ganttTask?->task_code,
                'task_name' => $link->ganttTask?->name,
                'task_start' => $link->ganttTask?->start_date?->format('Y-m-d'),
                'task_end' => $link->ganttTask?->end_date?->format('Y-m-d'),
                'boq_code' => $link->boqItem?->code,
                'boq_description' => $link->boqItem?->description,
                'quantity' => $link->quantity,
                'unit' => $link->quantity_unit,
                'unit_cost' => $link->unit_cost,
                'total_cost' => $link->total_cost,
                'progress' => $link->progress_percent,
            ];
        }

        if ($format === 'csv') {
            $csv = '';
            if (!empty($data)) {
                $csv = implode(',', array_keys($data[0])) . "\n";
                foreach ($data as $row) {
                    $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v ?? '') . '"', $row)) . "\n";
                }
            }
            return $csv;
        }

        return json_encode($data, JSON_PRETTY_PRINT);
    }
}
