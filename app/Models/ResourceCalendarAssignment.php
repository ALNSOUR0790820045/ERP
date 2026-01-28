<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceCalendarAssignment extends Model
{
    use HasFactory;

    protected $table = 'resource_calendar_assignments';

    protected $fillable = [
        'resource_calendar_id',
        'project_resource_id',
        'employee_id',
        'equipment_id',
        'gantt_task_id',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function calendar(): BelongsTo
    {
        return $this->belongsTo(ResourceCalendar::class, 'resource_calendar_id');
    }

    public function projectResource(): BelongsTo
    {
        return $this->belongsTo(ProjectResource::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function ganttTask(): BelongsTo
    {
        return $this->belongsTo(GanttTask::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForEquipment($query, int $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }

    public function scopeForResource($query, int $resourceId)
    {
        return $query->where('project_resource_id', $resourceId);
    }

    public function scopeForTask($query, int $taskId)
    {
        return $query->where('gantt_task_id', $taskId);
    }

    public function scopeEffectiveOn($query, $date)
    {
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            });
    }

    public function scopeCurrent($query)
    {
        return $query->effectiveOn(now())->active();
    }

    // Methods
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function isEffectiveOn(\DateTimeInterface $date): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        if ($date < $this->effective_from) {
            return false;
        }
        
        if ($this->effective_to && $date > $this->effective_to) {
            return false;
        }
        
        return true;
    }

    public function getResourceTypeAttribute(): string
    {
        if ($this->employee_id) {
            return 'employee';
        }
        if ($this->equipment_id) {
            return 'equipment';
        }
        if ($this->project_resource_id) {
            return 'project_resource';
        }
        if ($this->gantt_task_id) {
            return 'task';
        }
        return 'unknown';
    }

    public function getResourceNameAttribute(): string
    {
        if ($this->employee) {
            return $this->employee->full_name ?? $this->employee->name;
        }
        if ($this->equipment) {
            return $this->equipment->name;
        }
        if ($this->projectResource) {
            return $this->projectResource->name;
        }
        if ($this->ganttTask) {
            return $this->ganttTask->name;
        }
        return '-';
    }

    public function extend(\DateTimeInterface $newEndDate): void
    {
        $this->update(['effective_to' => $newEndDate]);
    }

    public static function getCalendarForEmployee(int $employeeId, ?\DateTimeInterface $date = null): ?ResourceCalendar
    {
        $date = $date ?? now();
        
        $assignment = self::forEmployee($employeeId)
            ->effectiveOn($date)
            ->active()
            ->with('calendar')
            ->first();
            
        return $assignment?->calendar;
    }

    public static function getCalendarForEquipment(int $equipmentId, ?\DateTimeInterface $date = null): ?ResourceCalendar
    {
        $date = $date ?? now();
        
        $assignment = self::forEquipment($equipmentId)
            ->effectiveOn($date)
            ->active()
            ->with('calendar')
            ->first();
            
        return $assignment?->calendar;
    }
}
