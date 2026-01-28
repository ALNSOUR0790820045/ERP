<?php

namespace App\Services\ProjectManagement;

use App\Models\P6ImportExport;
use App\Models\P6ActivityMapping;
use App\Models\P6ResourceMapping;
use App\Models\Project;
use App\Models\GanttTask;
use App\Models\ProjectWbs;
use Illuminate\Support\Facades\Storage;

class P6ExportService
{
    protected ?P6ImportExport $exportRecord = null;
    protected ?Project $project = null;
    protected array $options = [];

    /**
     * Export project to P6 XER format
     */
    public function exportToXer(int $projectId, array $options = []): P6ImportExport
    {
        $this->project = Project::with([
            'ganttTasks',
            'projectWbs',
        ])->findOrFail($projectId);
        
        $this->options = $options;
        
        $fileName = sprintf('P6_Export_%s_%s.xer', 
            $this->project->code ?? $this->project->id,
            now()->format('Ymd_His')
        );
        
        $this->exportRecord = P6ImportExport::create([
            'project_id' => $projectId,
            'type' => 'export',
            'format' => 'xer',
            'file_name' => $fileName,
            'status' => 'pending',
            'total_activities' => $this->project->ganttTasks->count(),
            'options' => $options,
            'created_by' => auth()->id(),
        ]);

        try {
            $this->exportRecord->markAsProcessing();
            
            $xerContent = $this->generateXerContent();
            
            $filePath = 'exports/p6/' . $fileName;
            Storage::put($filePath, $xerContent);
            
            $this->exportRecord->update([
                'file_path' => $filePath,
                'file_size' => strlen($xerContent),
                'processed_activities' => $this->project->ganttTasks->count(),
            ]);
            
            $this->exportRecord->markAsCompleted();
            
        } catch (\Exception $e) {
            $this->exportRecord->markAsFailed([
                'message' => $e->getMessage(),
            ]);
        }

        return $this->exportRecord;
    }

    /**
     * Export project to P6 XML (PMXML) format
     */
    public function exportToXml(int $projectId, array $options = []): P6ImportExport
    {
        $this->project = Project::with([
            'ganttTasks.dependencies',
            'ganttTasks.resources',
            'projectWbs',
        ])->findOrFail($projectId);
        
        $this->options = $options;
        
        $fileName = sprintf('P6_Export_%s_%s.xml', 
            $this->project->code ?? $this->project->id,
            now()->format('Ymd_His')
        );
        
        $this->exportRecord = P6ImportExport::create([
            'project_id' => $projectId,
            'type' => 'export',
            'format' => 'xml',
            'file_name' => $fileName,
            'status' => 'pending',
            'total_activities' => $this->project->ganttTasks->count(),
            'options' => $options,
            'created_by' => auth()->id(),
        ]);

        try {
            $this->exportRecord->markAsProcessing();
            
            $xmlContent = $this->generateXmlContent();
            
            $filePath = 'exports/p6/' . $fileName;
            Storage::put($filePath, $xmlContent);
            
            $this->exportRecord->update([
                'file_path' => $filePath,
                'file_size' => strlen($xmlContent),
                'processed_activities' => $this->project->ganttTasks->count(),
            ]);
            
            $this->exportRecord->markAsCompleted();
            
        } catch (\Exception $e) {
            $this->exportRecord->markAsFailed([
                'message' => $e->getMessage(),
            ]);
        }

        return $this->exportRecord;
    }

    /**
     * Generate XER file content
     */
    protected function generateXerContent(): string
    {
        $lines = [];
        
        // Header
        $lines[] = 'ERMHDR	8.0	' . now()->format('Y-m-d') . '	Project	admin	admin	CCMS ERP	USD';
        $lines[] = '';
        
        // Currency table
        $lines[] = '%T	CURRTYPE';
        $lines[] = '%F	curr_id	curr_short_name	curr_symbol';
        $lines[] = '%R	1	JOD	JD';
        $lines[] = '';
        
        // Project table
        $lines[] = '%T	PROJECT';
        $lines[] = '%F	proj_id	proj_short_name	orig_proj_id	source_proj_id	base_type_id	clndr_id	plan_start_date	plan_end_date	scd_end_date	last_recalc_date	act_start_date	act_end_date';
        $lines[] = sprintf('%R	%d	%s	%s	%s	1	1	%s	%s	%s	%s	%s	%s',
            $this->project->id,
            $this->project->code ?? 'PRJ' . $this->project->id,
            $this->project->code ?? 'PRJ' . $this->project->id,
            $this->project->code ?? 'PRJ' . $this->project->id,
            $this->formatP6Date($this->project->start_date),
            $this->formatP6Date($this->project->end_date),
            $this->formatP6Date($this->project->end_date),
            $this->formatP6Date(now()),
            $this->formatP6Date($this->project->actual_start_date),
            $this->formatP6Date($this->project->actual_end_date)
        );
        $lines[] = '';
        
        // WBS table
        $lines[] = '%T	PROJWBS';
        $lines[] = '%F	wbs_id	proj_id	obs_id	parent_wbs_id	wbs_short_name	wbs_name	seq_num	status_code';
        
        $wbsIndex = 1;
        $wbsMapping = [];
        
        foreach ($this->project->projectWbs ?? [] as $wbs) {
            $wbsMapping[$wbs->id] = $wbsIndex;
            $lines[] = sprintf('%R	%d	%d	1	%s	%s	%s	%d	WS_Open',
                $wbsIndex,
                $this->project->id,
                $wbs->parent_id ? ($wbsMapping[$wbs->parent_id] ?? '') : '',
                $wbs->code ?? 'WBS' . $wbsIndex,
                $wbs->name ?? '',
                $wbs->level ?? 1
            );
            $wbsIndex++;
        }
        $lines[] = '';
        
        // Task table
        $lines[] = '%T	TASK';
        $lines[] = '%F	task_id	proj_id	wbs_id	task_code	task_name	task_type	duration_type	status_code	phys_complete_pct	target_drtn_hr_cnt	remain_drtn_hr_cnt	early_start_date	early_end_date	late_start_date	late_end_date	act_start_date	act_end_date	total_float_hr_cnt	free_float_hr_cnt';
        
        $taskIndex = 1;
        $taskMapping = [];
        
        foreach ($this->project->ganttTasks ?? [] as $task) {
            $taskMapping[$task->id] = $taskIndex;
            
            $taskType = $task->is_milestone ? 'TT_Mile' : 'TT_Task';
            $statusCode = $this->mapStatusToP6($task->status);
            $duration = ($task->duration ?? 0) * 8; // Days to hours
            $remainingDuration = $duration * (1 - ($task->progress ?? 0) / 100);
            
            $lines[] = sprintf('%R	%d	%d	%s	%s	%s	%s	DT_FixedDrtn	%s	%.0f	%d	%d	%s	%s	%s	%s	%s	%s	%.0f	%.0f',
                $taskIndex,
                $this->project->id,
                $task->project_wbs_id ? ($wbsMapping[$task->project_wbs_id] ?? '') : '',
                $task->task_code ?? 'A' . str_pad($taskIndex, 4, '0', STR_PAD_LEFT),
                $this->escapeXerValue($task->name ?? ''),
                $taskType,
                $statusCode,
                $task->progress ?? 0,
                $duration,
                $remainingDuration,
                $this->formatP6Date($task->planned_start ?? $task->start_date),
                $this->formatP6Date($task->planned_end ?? $task->end_date),
                $this->formatP6Date($task->late_start ?? $task->start_date),
                $this->formatP6Date($task->late_end ?? $task->end_date),
                $this->formatP6Date($task->actual_start),
                $this->formatP6Date($task->actual_end),
                ($task->total_float ?? 0) * 8,
                ($task->free_float ?? 0) * 8
            );
            
            $taskIndex++;
            $this->exportRecord->increment('processed_activities');
        }
        $lines[] = '';
        
        // Task Predecessors (Dependencies)
        $lines[] = '%T	TASKPRED';
        $lines[] = '%F	task_pred_id	task_id	pred_task_id	proj_id	pred_proj_id	pred_type	lag_hr_cnt';
        
        $predIndex = 1;
        foreach ($this->project->ganttTasks ?? [] as $task) {
            foreach ($task->dependencies ?? [] as $dependency) {
                $lines[] = sprintf('%R	%d	%d	%d	%d	%d	PR_%s	%d',
                    $predIndex,
                    $taskMapping[$task->id] ?? 0,
                    $taskMapping[$dependency->predecessor_id] ?? 0,
                    $this->project->id,
                    $this->project->id,
                    $dependency->dependency_type ?? 'FS',
                    ($dependency->lag ?? 0) * 8
                );
                $predIndex++;
            }
        }
        $lines[] = '';
        
        // End marker
        $lines[] = '%E';
        
        return implode("\r\n", $lines);
    }

    /**
     * Generate PMXML content
     */
    protected function generateXmlContent(): string
    {
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // Root element
        $root = $xml->createElement('APIBusinessObjects');
        $root->setAttribute('xmlns', 'http://xmlns.oracle.com/Primavera/P6/V8.3/API/BusinessObjects');
        $xml->appendChild($root);
        
        // Project element
        $projectEl = $xml->createElement('Project');
        $root->appendChild($projectEl);
        
        $this->addXmlElement($xml, $projectEl, 'ObjectId', $this->project->id);
        $this->addXmlElement($xml, $projectEl, 'Id', $this->project->code ?? 'PRJ' . $this->project->id);
        $this->addXmlElement($xml, $projectEl, 'Name', $this->project->name ?? '');
        $this->addXmlElement($xml, $projectEl, 'StartDate', $this->formatP6XmlDate($this->project->start_date));
        $this->addXmlElement($xml, $projectEl, 'FinishDate', $this->formatP6XmlDate($this->project->end_date));
        $this->addXmlElement($xml, $projectEl, 'DataDate', $this->formatP6XmlDate(now()));
        
        // WBS elements
        foreach ($this->project->projectWbs ?? [] as $wbs) {
            $wbsEl = $xml->createElement('WBS');
            $projectEl->appendChild($wbsEl);
            
            $this->addXmlElement($xml, $wbsEl, 'ObjectId', $wbs->id);
            $this->addXmlElement($xml, $wbsEl, 'Code', $wbs->code ?? '');
            $this->addXmlElement($xml, $wbsEl, 'Name', $wbs->name ?? '');
            
            if ($wbs->parent_id) {
                $this->addXmlElement($xml, $wbsEl, 'ParentObjectId', $wbs->parent_id);
            }
        }
        
        // Activity elements
        foreach ($this->project->ganttTasks ?? [] as $task) {
            $activityEl = $xml->createElement('Activity');
            $projectEl->appendChild($activityEl);
            
            $this->addXmlElement($xml, $activityEl, 'ObjectId', $task->id);
            $this->addXmlElement($xml, $activityEl, 'Id', $task->task_code ?? 'A' . $task->id);
            $this->addXmlElement($xml, $activityEl, 'Name', $task->name ?? '');
            $this->addXmlElement($xml, $activityEl, 'Type', $task->is_milestone ? 'Start Milestone' : 'Task Dependent');
            $this->addXmlElement($xml, $activityEl, 'Status', $this->mapStatusToP6Xml($task->status));
            $this->addXmlElement($xml, $activityEl, 'StartDate', $this->formatP6XmlDate($task->start_date));
            $this->addXmlElement($xml, $activityEl, 'FinishDate', $this->formatP6XmlDate($task->end_date));
            $this->addXmlElement($xml, $activityEl, 'PlannedDuration', $task->duration ?? 0);
            $this->addXmlElement($xml, $activityEl, 'PercentComplete', $task->progress ?? 0);
            
            if ($task->project_wbs_id) {
                $this->addXmlElement($xml, $activityEl, 'WBSObjectId', $task->project_wbs_id);
            }
            
            // Add predecessors
            foreach ($task->dependencies ?? [] as $dep) {
                $predEl = $xml->createElement('Predecessor');
                $activityEl->appendChild($predEl);
                
                $this->addXmlElement($xml, $predEl, 'PredecessorActivityObjectId', $dep->predecessor_id);
                $this->addXmlElement($xml, $predEl, 'Type', $dep->dependency_type ?? 'Finish to Start');
                $this->addXmlElement($xml, $predEl, 'Lag', $dep->lag ?? 0);
            }
        }
        
        return $xml->saveXML();
    }

    protected function addXmlElement(\DOMDocument $xml, \DOMElement $parent, string $name, $value): void
    {
        $element = $xml->createElement($name, htmlspecialchars((string) $value));
        $parent->appendChild($element);
    }

    protected function formatP6Date($date): string
    {
        if (!$date) return '';
        
        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return '';
        }
    }

    protected function formatP6XmlDate($date): string
    {
        if (!$date) return '';
        
        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d\TH:i:s');
        } catch (\Exception $e) {
            return '';
        }
    }

    protected function escapeXerValue(string $value): string
    {
        return str_replace(["\t", "\r", "\n"], [' ', '', ' '], $value);
    }

    protected function mapStatusToP6(string $status): string
    {
        return match ($status) {
            'not_started' => 'TK_NotStart',
            'in_progress' => 'TK_Active',
            'completed' => 'TK_Complete',
            default => 'TK_NotStart',
        };
    }

    protected function mapStatusToP6Xml(string $status): string
    {
        return match ($status) {
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            default => 'Not Started',
        };
    }

    /**
     * Get download URL for exported file
     */
    public function getDownloadUrl(): ?string
    {
        if (!$this->exportRecord || !$this->exportRecord->file_path) {
            return null;
        }
        
        return Storage::url($this->exportRecord->file_path);
    }
}
