<?php

namespace App\Services\ProjectManagement;

use App\Models\MspImportExport;
use App\Models\MspTaskMapping;
use App\Models\Project;
use App\Models\GanttTask;
use App\Models\ProjectWbs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MsProjectService
{
    protected ?MspImportExport $importExport = null;
    protected ?Project $project = null;
    protected array $taskMappings = [];
    protected array $resourceMappings = [];
    protected array $errors = [];

    /**
     * Import MS Project XML (MSPDI) file
     */
    public function importMspdi(string $filePath, int $projectId, array $options = []): MspImportExport
    {
        $this->project = Project::findOrFail($projectId);
        
        $this->importExport = MspImportExport::create([
            'project_id' => $projectId,
            'type' => 'import',
            'format' => 'mspdi',
            'file_name' => basename($filePath),
            'file_path' => $filePath,
            'file_size' => file_exists($filePath) ? filesize($filePath) : null,
            'status' => 'pending',
            'options' => $options,
            'created_by' => auth()->id(),
        ]);

        try {
            $this->importExport->markAsProcessing();
            
            $xml = simplexml_load_file($filePath);
            if ($xml === false) {
                throw new \Exception('Failed to parse MS Project XML file');
            }
            
            // Get MS Project version
            $this->importExport->update([
                'msp_version' => (string) ($xml->SaveVersion ?? 'Unknown'),
            ]);
            
            DB::beginTransaction();
            
            // Process calendar first
            $this->processCalendars($xml->Calendars ?? null);
            
            // Process resources
            $resources = $xml->Resources->Resource ?? [];
            $this->importExport->update(['total_resources' => count($resources)]);
            $this->processResources($resources);
            
            // Process tasks
            $tasks = $xml->Tasks->Task ?? [];
            $this->importExport->update(['total_tasks' => count($tasks)]);
            $this->processTasks($tasks, $options);
            
            // Process assignments
            $this->processAssignments($xml->Assignments->Assignment ?? []);
            
            DB::commit();
            
            $this->importExport->markAsCompleted();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->importExport->markAsFailed([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $this->importExport;
    }

    /**
     * Export project to MS Project XML (MSPDI) format
     */
    public function exportToMspdi(int $projectId, array $options = []): MspImportExport
    {
        $this->project = Project::with([
            'ganttTasks.dependencies',
            'ganttTasks.resources',
        ])->findOrFail($projectId);
        
        $fileName = sprintf('MSProject_Export_%s_%s.xml', 
            $this->project->code ?? $this->project->id,
            now()->format('Ymd_His')
        );
        
        $this->importExport = MspImportExport::create([
            'project_id' => $projectId,
            'type' => 'export',
            'format' => 'mspdi',
            'file_name' => $fileName,
            'status' => 'pending',
            'total_tasks' => $this->project->ganttTasks->count(),
            'options' => $options,
            'created_by' => auth()->id(),
        ]);

        try {
            $this->importExport->markAsProcessing();
            
            $xmlContent = $this->generateMspdiContent();
            
            $filePath = 'exports/msp/' . $fileName;
            Storage::put($filePath, $xmlContent);
            
            $this->importExport->update([
                'file_path' => $filePath,
                'file_size' => strlen($xmlContent),
                'processed_tasks' => $this->project->ganttTasks->count(),
            ]);
            
            $this->importExport->markAsCompleted();
            
        } catch (\Exception $e) {
            $this->importExport->markAsFailed([
                'message' => $e->getMessage(),
            ]);
        }

        return $this->importExport;
    }

    /**
     * Process calendars from MS Project
     */
    protected function processCalendars($calendars): void
    {
        // Store calendar information for resource assignment
        // Implementation depends on ResourceCalendar model usage
    }

    /**
     * Process resources from MS Project
     */
    protected function processResources($resources): void
    {
        foreach ($resources as $resource) {
            $uid = (int) $resource->UID;
            
            // Skip null resource (UID = 0)
            if ($uid === 0) continue;
            
            $this->resourceMappings[$uid] = [
                'name' => (string) $resource->Name,
                'type' => $this->mapMspResourceType((int) ($resource->Type ?? 1)),
                'cost' => (float) ($resource->StandardRate ?? 0),
            ];
            
            $this->importExport->increment('processed_resources');
        }
    }

    /**
     * Process tasks from MS Project
     */
    protected function processTasks($tasks, array $options = []): void
    {
        $createNew = $options['create_new'] ?? true;
        $updateExisting = $options['update_existing'] ?? false;
        
        // First pass: create all tasks
        foreach ($tasks as $task) {
            $uid = (int) $task->UID;
            
            // Skip project summary task (UID = 0)
            if ($uid === 0) continue;
            
            $taskName = (string) $task->Name;
            $wbsCode = (string) ($task->WBS ?? '');
            $outlineLevel = (int) ($task->OutlineLevel ?? 0);
            $isMilestone = ((int) ($task->Milestone ?? 0)) === 1;
            $isSummary = ((int) ($task->Summary ?? 0)) === 1;
            
            try {
                // Try to find existing task
                $ganttTask = GanttTask::where('project_id', $this->project->id)
                    ->where('task_code', $wbsCode ?: 'T' . $uid)
                    ->first();
                
                $taskData = [
                    'project_id' => $this->project->id,
                    'task_code' => $wbsCode ?: 'T' . $uid,
                    'name' => $taskName,
                    'start_date' => $this->parseMspDate((string) ($task->Start ?? '')),
                    'end_date' => $this->parseMspDate((string) ($task->Finish ?? '')),
                    'duration' => $this->parseMspDuration((string) ($task->Duration ?? '')),
                    'progress' => (float) ($task->PercentComplete ?? 0),
                    'is_milestone' => $isMilestone,
                    'is_summary' => $isSummary,
                    'wbs_level' => $outlineLevel,
                    'priority' => $this->mapMspPriority((int) ($task->Priority ?? 500)),
                    'status' => $this->determineStatus($task),
                    'constraint_type' => $this->mapMspConstraint((int) ($task->ConstraintType ?? 0)),
                    'constraint_date' => $this->parseMspDate((string) ($task->ConstraintDate ?? '')),
                    'notes' => (string) ($task->Notes ?? ''),
                ];
                
                $mappingStatus = 'skipped';
                
                if ($ganttTask && $updateExisting) {
                    $ganttTask->update($taskData);
                    $mappingStatus = 'mapped';
                } elseif (!$ganttTask && $createNew) {
                    $ganttTask = GanttTask::create($taskData);
                    $mappingStatus = 'created';
                }
                
                if ($ganttTask) {
                    $this->taskMappings[$uid] = $ganttTask->id;
                    
                    MspTaskMapping::create([
                        'msp_import_export_id' => $this->importExport->id,
                        'msp_task_uid' => $uid,
                        'msp_task_name' => $taskName,
                        'msp_outline_level' => $outlineLevel,
                        'msp_wbs_code' => $wbsCode,
                        'gantt_task_id' => $ganttTask->id,
                        'mapping_status' => $mappingStatus,
                        'msp_data' => $this->xmlToArray($task),
                    ]);
                }
                
                $this->importExport->increment('processed_tasks');
                
            } catch (\Exception $e) {
                $this->errors[] = "Error processing task {$taskName}: " . $e->getMessage();
                
                MspTaskMapping::create([
                    'msp_import_export_id' => $this->importExport->id,
                    'msp_task_uid' => $uid,
                    'msp_task_name' => $taskName,
                    'msp_outline_level' => $outlineLevel,
                    'msp_wbs_code' => $wbsCode,
                    'mapping_status' => 'error',
                    'msp_data' => ['error' => $e->getMessage()],
                ]);
            }
        }
        
        // Second pass: process predecessors
        foreach ($tasks as $task) {
            $uid = (int) $task->UID;
            $ganttTaskId = $this->taskMappings[$uid] ?? null;
            
            if (!$ganttTaskId) continue;
            
            foreach ($task->PredecessorLink ?? [] as $link) {
                $predUid = (int) $link->PredecessorUID;
                $predGanttId = $this->taskMappings[$predUid] ?? null;
                
                if ($predGanttId) {
                    DB::table('gantt_dependencies')->insertOrIgnore([
                        'gantt_task_id' => $ganttTaskId,
                        'predecessor_id' => $predGanttId,
                        'dependency_type' => $this->mapMspLinkType((int) ($link->Type ?? 1)),
                        'lag' => $this->parseMspDuration((string) ($link->LinkLag ?? '')),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
        
        // Third pass: set parent relationships
        foreach ($tasks as $task) {
            $uid = (int) $task->UID;
            $ganttTaskId = $this->taskMappings[$uid] ?? null;
            $parentUid = (int) ($task->OutlineNumber ?? 0);
            
            // Determine parent from outline structure
            // This is simplified - actual implementation would need to track outline hierarchy
        }
    }

    /**
     * Process resource assignments
     */
    protected function processAssignments($assignments): void
    {
        foreach ($assignments as $assignment) {
            $taskUid = (int) $assignment->TaskUID;
            $resourceUid = (int) $assignment->ResourceUID;
            
            $ganttTaskId = $this->taskMappings[$taskUid] ?? null;
            $resource = $this->resourceMappings[$resourceUid] ?? null;
            
            if ($ganttTaskId && $resource) {
                DB::table('gantt_resources')->insertOrIgnore([
                    'gantt_task_id' => $ganttTaskId,
                    'resource_name' => $resource['name'],
                    'resource_type' => $resource['type'],
                    'units' => (float) ($assignment->Units ?? 1) * 100,
                    'cost' => (float) ($assignment->Cost ?? 0) / 100, // Convert from cents
                    'work' => $this->parseMspDuration((string) ($assignment->Work ?? '')),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Generate MSPDI XML content
     */
    protected function generateMspdiContent(): string
    {
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // Root Project element
        $projectEl = $xml->createElement('Project');
        $projectEl->setAttribute('xmlns', 'http://schemas.microsoft.com/project');
        $xml->appendChild($projectEl);
        
        // Project properties
        $this->addXmlElement($xml, $projectEl, 'Name', $this->project->name ?? '');
        $this->addXmlElement($xml, $projectEl, 'Title', $this->project->name ?? '');
        $this->addXmlElement($xml, $projectEl, 'CreationDate', now()->format('Y-m-d\TH:i:s'));
        $this->addXmlElement($xml, $projectEl, 'StartDate', $this->formatMspDate($this->project->start_date));
        $this->addXmlElement($xml, $projectEl, 'FinishDate', $this->formatMspDate($this->project->end_date));
        $this->addXmlElement($xml, $projectEl, 'ScheduleFromStart', '1');
        $this->addXmlElement($xml, $projectEl, 'CalendarUID', '1');
        
        // Calendar
        $calendarsEl = $xml->createElement('Calendars');
        $projectEl->appendChild($calendarsEl);
        $this->addDefaultCalendar($xml, $calendarsEl);
        
        // Tasks
        $tasksEl = $xml->createElement('Tasks');
        $projectEl->appendChild($tasksEl);
        
        // Add project summary task (UID 0)
        $summaryTask = $xml->createElement('Task');
        $tasksEl->appendChild($summaryTask);
        $this->addXmlElement($xml, $summaryTask, 'UID', '0');
        $this->addXmlElement($xml, $summaryTask, 'ID', '0');
        $this->addXmlElement($xml, $summaryTask, 'Name', $this->project->name ?? 'Project');
        $this->addXmlElement($xml, $summaryTask, 'Type', '1');
        $this->addXmlElement($xml, $summaryTask, 'IsNull', '0');
        $this->addXmlElement($xml, $summaryTask, 'Summary', '1');
        
        // Add project tasks
        $taskIndex = 1;
        $uidMapping = [];
        
        foreach ($this->project->ganttTasks ?? [] as $task) {
            $uidMapping[$task->id] = $taskIndex;
            
            $taskEl = $xml->createElement('Task');
            $tasksEl->appendChild($taskEl);
            
            $this->addXmlElement($xml, $taskEl, 'UID', (string) $taskIndex);
            $this->addXmlElement($xml, $taskEl, 'ID', (string) $taskIndex);
            $this->addXmlElement($xml, $taskEl, 'Name', $task->name ?? '');
            $this->addXmlElement($xml, $taskEl, 'Type', '1');
            $this->addXmlElement($xml, $taskEl, 'IsNull', '0');
            $this->addXmlElement($xml, $taskEl, 'WBS', $task->task_code ?? $taskIndex);
            $this->addXmlElement($xml, $taskEl, 'OutlineLevel', (string) ($task->wbs_level ?? 1));
            $this->addXmlElement($xml, $taskEl, 'Start', $this->formatMspDate($task->start_date));
            $this->addXmlElement($xml, $taskEl, 'Finish', $this->formatMspDate($task->end_date));
            $this->addXmlElement($xml, $taskEl, 'Duration', $this->formatMspDuration($task->duration ?? 0));
            $this->addXmlElement($xml, $taskEl, 'PercentComplete', (string) ($task->progress ?? 0));
            $this->addXmlElement($xml, $taskEl, 'Milestone', $task->is_milestone ? '1' : '0');
            $this->addXmlElement($xml, $taskEl, 'Summary', $task->is_summary ? '1' : '0');
            $this->addXmlElement($xml, $taskEl, 'Critical', $task->is_critical ? '1' : '0');
            $this->addXmlElement($xml, $taskEl, 'Priority', '500');
            $this->addXmlElement($xml, $taskEl, 'CalendarUID', '1');
            
            if ($task->notes) {
                $this->addXmlElement($xml, $taskEl, 'Notes', $task->notes);
            }
            
            $taskIndex++;
        }
        
        // Add predecessor links
        $taskIndex = 1;
        foreach ($this->project->ganttTasks ?? [] as $task) {
            if ($task->dependencies && $task->dependencies->count() > 0) {
                $taskEl = $tasksEl->getElementsByTagName('Task')->item($taskIndex);
                
                foreach ($task->dependencies as $dep) {
                    $predUid = $uidMapping[$dep->predecessor_id] ?? null;
                    if ($predUid) {
                        $linkEl = $xml->createElement('PredecessorLink');
                        $taskEl->appendChild($linkEl);
                        
                        $this->addXmlElement($xml, $linkEl, 'PredecessorUID', (string) $predUid);
                        $this->addXmlElement($xml, $linkEl, 'Type', $this->mapToMspLinkType($dep->dependency_type ?? 'FS'));
                        $this->addXmlElement($xml, $linkEl, 'LinkLag', $this->formatMspDuration($dep->lag ?? 0));
                    }
                }
            }
            $taskIndex++;
        }
        
        // Resources
        $resourcesEl = $xml->createElement('Resources');
        $projectEl->appendChild($resourcesEl);
        
        // Add null resource
        $nullResource = $xml->createElement('Resource');
        $resourcesEl->appendChild($nullResource);
        $this->addXmlElement($xml, $nullResource, 'UID', '0');
        
        // Assignments
        $assignmentsEl = $xml->createElement('Assignments');
        $projectEl->appendChild($assignmentsEl);
        
        return $xml->saveXML();
    }

    protected function addDefaultCalendar(\DOMDocument $xml, \DOMElement $parent): void
    {
        $calendar = $xml->createElement('Calendar');
        $parent->appendChild($calendar);
        
        $this->addXmlElement($xml, $calendar, 'UID', '1');
        $this->addXmlElement($xml, $calendar, 'Name', 'Standard');
        $this->addXmlElement($xml, $calendar, 'IsBaseCalendar', '1');
        
        $weekDays = $xml->createElement('WeekDays');
        $calendar->appendChild($weekDays);
        
        // Add working days (Monday to Friday)
        for ($day = 1; $day <= 7; $day++) {
            $weekDay = $xml->createElement('WeekDay');
            $weekDays->appendChild($weekDay);
            
            $this->addXmlElement($xml, $weekDay, 'DayType', (string) $day);
            $this->addXmlElement($xml, $weekDay, 'DayWorking', ($day >= 1 && $day <= 5) ? '1' : '0');
            
            if ($day >= 1 && $day <= 5) {
                $workingTimes = $xml->createElement('WorkingTimes');
                $weekDay->appendChild($workingTimes);
                
                $workingTime = $xml->createElement('WorkingTime');
                $workingTimes->appendChild($workingTime);
                $this->addXmlElement($xml, $workingTime, 'FromTime', '08:00:00');
                $this->addXmlElement($xml, $workingTime, 'ToTime', '17:00:00');
            }
        }
    }

    protected function addXmlElement(\DOMDocument $xml, \DOMElement $parent, string $name, string $value): void
    {
        $element = $xml->createElement($name, htmlspecialchars($value));
        $parent->appendChild($element);
    }

    protected function xmlToArray(\SimpleXMLElement $xml): array
    {
        return json_decode(json_encode($xml), true);
    }

    protected function parseMspDate(?string $date): ?string
    {
        if (!$date) return null;
        
        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function formatMspDate($date): string
    {
        if (!$date) return '';
        
        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d\TH:i:s');
        } catch (\Exception $e) {
            return '';
        }
    }

    protected function parseMspDuration(?string $duration): int
    {
        if (!$duration) return 0;
        
        // Parse ISO 8601 duration (e.g., PT8H0M0S, P5D)
        if (preg_match('/PT(\d+)H/', $duration, $matches)) {
            return (int) ($matches[1] / 8); // Convert hours to days
        }
        
        if (preg_match('/P(\d+)D/', $duration, $matches)) {
            return (int) $matches[1];
        }
        
        return 0;
    }

    protected function formatMspDuration(int $days): string
    {
        $hours = $days * 8;
        return "PT{$hours}H0M0S";
    }

    protected function mapMspResourceType(int $type): string
    {
        return match ($type) {
            0 => 'material',
            1 => 'labor',
            2 => 'expense',
            default => 'labor',
        };
    }

    protected function mapMspPriority(int $priority): string
    {
        if ($priority < 300) return 'high';
        if ($priority < 700) return 'medium';
        return 'low';
    }

    protected function mapMspConstraint(int $type): ?string
    {
        return match ($type) {
            0 => 'as_soon_as_possible',
            1 => 'as_late_as_possible',
            2 => 'must_start_on',
            3 => 'must_finish_on',
            4 => 'start_no_earlier_than',
            5 => 'start_no_later_than',
            6 => 'finish_no_earlier_than',
            7 => 'finish_no_later_than',
            default => null,
        };
    }

    protected function mapMspLinkType(int $type): string
    {
        return match ($type) {
            0 => 'FF',
            1 => 'FS',
            2 => 'SF',
            3 => 'SS',
            default => 'FS',
        };
    }

    protected function mapToMspLinkType(string $type): string
    {
        return match ($type) {
            'FF' => '0',
            'FS' => '1',
            'SF' => '2',
            'SS' => '3',
            default => '1',
        };
    }

    protected function determineStatus(\SimpleXMLElement $task): string
    {
        $percentComplete = (float) ($task->PercentComplete ?? 0);
        
        if ($percentComplete >= 100) {
            return 'completed';
        }
        
        if ($percentComplete > 0) {
            return 'in_progress';
        }
        
        return 'not_started';
    }
}
