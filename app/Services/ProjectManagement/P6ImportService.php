<?php

namespace App\Services\ProjectManagement;

use App\Models\P6ImportExport;
use App\Models\P6ActivityMapping;
use App\Models\P6ResourceMapping;
use App\Models\Project;
use App\Models\GanttTask;
use App\Models\ProjectWbs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class P6ImportService
{
    protected array $activityMappings = [];
    protected array $resourceMappings = [];
    protected array $wbsMappings = [];
    protected array $errors = [];
    protected ?P6ImportExport $importRecord = null;
    protected ?Project $project = null;

    /**
     * Import P6 XER file
     */
    public function importXer(string $filePath, int $projectId, array $options = []): P6ImportExport
    {
        $this->project = Project::findOrFail($projectId);
        
        // Create import record
        $this->importRecord = P6ImportExport::create([
            'project_id' => $projectId,
            'type' => 'import',
            'format' => 'xer',
            'file_name' => basename($filePath),
            'file_path' => $filePath,
            'file_size' => file_exists($filePath) ? filesize($filePath) : null,
            'status' => 'pending',
            'options' => $options,
            'created_by' => auth()->id(),
        ]);

        try {
            $this->importRecord->markAsProcessing();
            
            $content = $this->readXerFile($filePath);
            $tables = $this->parseXerContent($content);
            
            DB::beginTransaction();
            
            // Process WBS first
            if (isset($tables['PROJWBS'])) {
                $this->processWbs($tables['PROJWBS']);
            }
            
            // Process activities
            if (isset($tables['TASK'])) {
                $this->importRecord->update(['total_activities' => count($tables['TASK'])]);
                $this->processActivities($tables['TASK'], $options);
            }
            
            // Process resources
            if (isset($tables['RSRC'])) {
                $this->importRecord->update(['total_resources' => count($tables['RSRC'])]);
                $this->processResources($tables['RSRC']);
            }
            
            // Process relationships/dependencies
            if (isset($tables['TASKPRED'])) {
                $this->processRelationships($tables['TASKPRED']);
            }
            
            // Process resource assignments
            if (isset($tables['TASKRSRC'])) {
                $this->processResourceAssignments($tables['TASKRSRC']);
            }
            
            DB::commit();
            
            $this->importRecord->markAsCompleted();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->importRecord->markAsFailed([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $this->importRecord;
    }

    /**
     * Import P6 XML file
     */
    public function importXml(string $filePath, int $projectId, array $options = []): P6ImportExport
    {
        $this->project = Project::findOrFail($projectId);
        
        $this->importRecord = P6ImportExport::create([
            'project_id' => $projectId,
            'type' => 'import',
            'format' => 'xml',
            'file_name' => basename($filePath),
            'file_path' => $filePath,
            'file_size' => file_exists($filePath) ? filesize($filePath) : null,
            'status' => 'pending',
            'options' => $options,
            'created_by' => auth()->id(),
        ]);

        try {
            $this->importRecord->markAsProcessing();
            
            $xml = simplexml_load_file($filePath);
            if ($xml === false) {
                throw new \Exception('Failed to parse XML file');
            }
            
            DB::beginTransaction();
            
            // Register namespaces
            $namespaces = $xml->getNamespaces(true);
            
            // Process projects
            foreach ($xml->Project ?? [] as $project) {
                $this->processXmlProject($project);
            }
            
            DB::commit();
            
            $this->importRecord->markAsCompleted();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->importRecord->markAsFailed([
                'message' => $e->getMessage(),
            ]);
        }

        return $this->importRecord;
    }

    /**
     * Read XER file content
     */
    protected function readXerFile(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }
        
        return file_get_contents($filePath);
    }

    /**
     * Parse XER content into tables
     */
    protected function parseXerContent(string $content): array
    {
        $tables = [];
        $currentTable = null;
        $currentFields = [];
        
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }
            
            // Table definition
            if (Str::startsWith($line, '%T')) {
                $currentTable = trim(substr($line, 2));
                $tables[$currentTable] = [];
                continue;
            }
            
            // Field definition
            if (Str::startsWith($line, '%F')) {
                $currentFields = explode("\t", substr($line, 2));
                $currentFields = array_map('trim', $currentFields);
                continue;
            }
            
            // Row data
            if (Str::startsWith($line, '%R') && $currentTable && !empty($currentFields)) {
                $values = explode("\t", substr($line, 2));
                $row = [];
                
                foreach ($currentFields as $index => $field) {
                    $row[$field] = $values[$index] ?? null;
                }
                
                $tables[$currentTable][] = $row;
            }
        }
        
        return $tables;
    }

    /**
     * Process WBS structure
     */
    protected function processWbs(array $wbsData): void
    {
        foreach ($wbsData as $wbs) {
            $wbsId = $wbs['wbs_id'] ?? null;
            if (!$wbsId) continue;
            
            $projectWbs = ProjectWbs::create([
                'project_id' => $this->project->id,
                'code' => $wbs['wbs_short_name'] ?? $wbsId,
                'name' => $wbs['wbs_name'] ?? $wbs['wbs_short_name'] ?? '',
                'description' => $wbs['wbs_name'] ?? '',
                'level' => $wbs['seq_num'] ?? 1,
                'parent_id' => $this->wbsMappings[$wbs['parent_wbs_id'] ?? ''] ?? null,
            ]);
            
            $this->wbsMappings[$wbsId] = $projectWbs->id;
        }
    }

    /**
     * Process activities from P6
     */
    protected function processActivities(array $activities, array $options = []): void
    {
        $createNew = $options['create_new'] ?? true;
        $updateExisting = $options['update_existing'] ?? false;
        
        foreach ($activities as $index => $activity) {
            $p6ActivityId = $activity['task_id'] ?? null;
            $activityCode = $activity['task_code'] ?? '';
            $activityName = $activity['task_name'] ?? '';
            
            if (!$p6ActivityId) {
                $this->errors[] = "Missing task_id at index {$index}";
                continue;
            }
            
            try {
                // Try to find existing task by code
                $ganttTask = GanttTask::where('project_id', $this->project->id)
                    ->where('task_code', $activityCode)
                    ->first();
                
                $taskData = [
                    'project_id' => $this->project->id,
                    'task_code' => $activityCode,
                    'name' => $activityName,
                    'start_date' => $this->parseP6Date($activity['act_start_date'] ?? $activity['early_start_date'] ?? null),
                    'end_date' => $this->parseP6Date($activity['act_end_date'] ?? $activity['early_end_date'] ?? null),
                    'planned_start' => $this->parseP6Date($activity['early_start_date'] ?? null),
                    'planned_end' => $this->parseP6Date($activity['early_end_date'] ?? null),
                    'duration' => $activity['target_drtn_hr_cnt'] ?? 0,
                    'progress' => $this->calculateProgress($activity),
                    'is_milestone' => ($activity['task_type'] ?? '') === 'TT_Mile',
                    'is_critical' => ($activity['total_float_hr_cnt'] ?? 999) <= 0,
                    'project_wbs_id' => $this->wbsMappings[$activity['wbs_id'] ?? ''] ?? null,
                    'status' => $this->mapTaskStatus($activity),
                    'total_float' => $activity['total_float_hr_cnt'] ?? null,
                    'free_float' => $activity['free_float_hr_cnt'] ?? null,
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
                    $this->activityMappings[$p6ActivityId] = $ganttTask->id;
                    
                    P6ActivityMapping::create([
                        'p6_import_export_id' => $this->importRecord->id,
                        'p6_activity_id' => $p6ActivityId,
                        'p6_activity_name' => $activityName,
                        'gantt_task_id' => $ganttTask->id,
                        'project_wbs_id' => $taskData['project_wbs_id'],
                        'mapping_status' => $mappingStatus,
                        'p6_data' => $activity,
                    ]);
                }
                
                $this->importRecord->increment('processed_activities');
                
            } catch (\Exception $e) {
                $this->errors[] = "Error processing activity {$activityCode}: " . $e->getMessage();
                
                P6ActivityMapping::create([
                    'p6_import_export_id' => $this->importRecord->id,
                    'p6_activity_id' => $p6ActivityId,
                    'p6_activity_name' => $activityName,
                    'mapping_status' => 'error',
                    'p6_data' => $activity,
                    'mapping_notes' => ['error' => $e->getMessage()],
                ]);
            }
        }
    }

    /**
     * Process resources from P6
     */
    protected function processResources(array $resources): void
    {
        foreach ($resources as $resource) {
            $resourceId = $resource['rsrc_id'] ?? null;
            if (!$resourceId) continue;
            
            $resourceType = $this->mapResourceType($resource['rsrc_type'] ?? '');
            
            P6ResourceMapping::create([
                'p6_import_export_id' => $this->importRecord->id,
                'p6_resource_id' => $resourceId,
                'p6_resource_name' => $resource['rsrc_name'] ?? '',
                'resource_type' => $resourceType,
                'mapping_status' => 'created',
                'p6_data' => $resource,
            ]);
            
            $this->resourceMappings[$resourceId] = $resource;
            $this->importRecord->increment('processed_resources');
        }
    }

    /**
     * Process task relationships/dependencies
     */
    protected function processRelationships(array $relationships): void
    {
        foreach ($relationships as $rel) {
            $predTaskId = $rel['pred_task_id'] ?? null;
            $taskId = $rel['task_id'] ?? null;
            
            if (!$predTaskId || !$taskId) continue;
            
            $predecessorGanttId = $this->activityMappings[$predTaskId] ?? null;
            $successorGanttId = $this->activityMappings[$taskId] ?? null;
            
            if ($predecessorGanttId && $successorGanttId) {
                // Create dependency in gantt_dependencies table
                DB::table('gantt_dependencies')->insertOrIgnore([
                    'gantt_task_id' => $successorGanttId,
                    'predecessor_id' => $predecessorGanttId,
                    'dependency_type' => $this->mapDependencyType($rel['pred_type'] ?? 'FS'),
                    'lag' => (int) ($rel['lag_hr_cnt'] ?? 0) / 8, // Convert hours to days
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Process resource assignments
     */
    protected function processResourceAssignments(array $assignments): void
    {
        foreach ($assignments as $assignment) {
            $taskId = $assignment['task_id'] ?? null;
            $resourceId = $assignment['rsrc_id'] ?? null;
            
            if (!$taskId || !$resourceId) continue;
            
            $ganttTaskId = $this->activityMappings[$taskId] ?? null;
            
            if ($ganttTaskId) {
                DB::table('gantt_resources')->insertOrIgnore([
                    'gantt_task_id' => $ganttTaskId,
                    'resource_name' => $this->resourceMappings[$resourceId]['rsrc_name'] ?? 'Unknown',
                    'resource_type' => $this->mapResourceType($this->resourceMappings[$resourceId]['rsrc_type'] ?? ''),
                    'units' => $assignment['target_qty'] ?? 1,
                    'cost' => $assignment['target_cost'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Process XML project data
     */
    protected function processXmlProject(\SimpleXMLElement $project): void
    {
        // Process WBS
        foreach ($project->WBS ?? [] as $wbs) {
            $this->processXmlWbs($wbs);
        }
        
        // Process Activities
        $activities = [];
        foreach ($project->Activity ?? [] as $activity) {
            $activities[] = $this->xmlToArray($activity);
        }
        $this->importRecord->update(['total_activities' => count($activities)]);
        
        foreach ($activities as $activity) {
            $this->processXmlActivity($activity);
        }
    }

    protected function processXmlWbs(\SimpleXMLElement $wbs): void
    {
        $wbsId = (string) $wbs->ObjectId;
        
        $projectWbs = ProjectWbs::create([
            'project_id' => $this->project->id,
            'code' => (string) $wbs->Code,
            'name' => (string) $wbs->Name,
        ]);
        
        $this->wbsMappings[$wbsId] = $projectWbs->id;
    }

    protected function processXmlActivity(array $activity): void
    {
        $ganttTask = GanttTask::create([
            'project_id' => $this->project->id,
            'task_code' => $activity['Id'] ?? '',
            'name' => $activity['Name'] ?? '',
            'start_date' => $this->parseP6Date($activity['StartDate'] ?? null),
            'end_date' => $this->parseP6Date($activity['FinishDate'] ?? null),
            'duration' => $activity['PlannedDuration'] ?? 0,
            'project_wbs_id' => $this->wbsMappings[$activity['WBSObjectId'] ?? ''] ?? null,
        ]);
        
        $this->activityMappings[$activity['ObjectId'] ?? ''] = $ganttTask->id;
        $this->importRecord->increment('processed_activities');
    }

    protected function xmlToArray(\SimpleXMLElement $xml): array
    {
        return json_decode(json_encode($xml), true);
    }

    protected function parseP6Date(?string $date): ?string
    {
        if (!$date) return null;
        
        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function calculateProgress(array $activity): float
    {
        if (($activity['status_code'] ?? '') === 'TK_Complete') {
            return 100;
        }
        
        return (float) ($activity['phys_complete_pct'] ?? 0);
    }

    protected function mapTaskStatus(array $activity): string
    {
        $status = $activity['status_code'] ?? '';
        
        return match ($status) {
            'TK_NotStart' => 'not_started',
            'TK_Active' => 'in_progress',
            'TK_Complete' => 'completed',
            default => 'not_started',
        };
    }

    protected function mapResourceType(string $type): string
    {
        return match ($type) {
            'RT_Labor' => 'labor',
            'RT_Equip' => 'equipment',
            'RT_Mat' => 'material',
            default => 'labor',
        };
    }

    protected function mapDependencyType(string $type): string
    {
        return match ($type) {
            'PR_FS' => 'FS',
            'PR_FF' => 'FF',
            'PR_SS' => 'SS',
            'PR_SF' => 'SF',
            default => 'FS',
        };
    }
}
