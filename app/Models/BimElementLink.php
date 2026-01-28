<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BimElementLink extends Model
{
    use HasFactory;

    protected $table = 'bim_element_links';

    protected $fillable = [
        'bim_model_id',
        'element_guid',
        'element_name',
        'element_type',
        'ifc_class',
        'gantt_task_id',
        'boq_item_id',
        'project_wbs_id',
        'quantity',
        'quantity_unit',
        'unit_cost',
        'total_cost',
        'status',
        'progress_percent',
        'properties',
        'materials',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'progress_percent' => 'decimal:2',
        'properties' => 'array',
        'materials' => 'array',
    ];

    // Relationships
    public function bimModel(): BelongsTo
    {
        return $this->belongsTo(BimModel::class);
    }

    public function ganttTask(): BelongsTo
    {
        return $this->belongsTo(GanttTask::class);
    }

    public function boqItem(): BelongsTo
    {
        return $this->belongsTo(BoqItem::class);
    }

    public function projectWbs(): BelongsTo
    {
        return $this->belongsTo(ProjectWbs::class);
    }

    // Scopes
    public function scopeNotStarted($query)
    {
        return $query->where('status', 'not_started');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeLinkedToSchedule($query)
    {
        return $query->whereNotNull('gantt_task_id');
    }

    public function scopeLinkedToBoq($query)
    {
        return $query->whereNotNull('boq_item_id');
    }

    public function scopeUnlinked($query)
    {
        return $query->whereNull('gantt_task_id')->whereNull('boq_item_id');
    }

    public function scopeByIfcClass($query, string $class)
    {
        return $query->where('ifc_class', $class);
    }

    // Methods
    public function calculateTotalCost(): void
    {
        if ($this->quantity && $this->unit_cost) {
            $this->total_cost = $this->quantity * $this->unit_cost;
            $this->save();
        }
    }

    public function updateProgress(float $percent): void
    {
        $this->progress_percent = min(100, max(0, $percent));
        
        if ($percent == 0) {
            $this->status = 'not_started';
        } elseif ($percent >= 100) {
            $this->status = 'completed';
        } else {
            $this->status = 'in_progress';
        }
        
        $this->save();
    }

    public function linkToTask(int $taskId): void
    {
        $this->update(['gantt_task_id' => $taskId]);
    }

    public function linkToBoq(int $boqItemId): void
    {
        $this->update(['boq_item_id' => $boqItemId]);
    }

    public function getIs4DLinkedAttribute(): bool
    {
        return $this->gantt_task_id !== null;
    }

    public function getIs5DLinkedAttribute(): bool
    {
        return $this->boq_item_id !== null || $this->total_cost !== null;
    }

    public function getStatusArabicAttribute(): string
    {
        $statuses = [
            'not_started' => 'لم يبدأ',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
        ];

        return $statuses[$this->status] ?? $this->status;
    }
}
