<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تخصيص مورد لمهمة
 * Gantt Task Resource
 */
class GanttTaskResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'resource_id',
        'units',
        'planned_hours',
        'actual_hours',
        'cost',
    ];

    protected $casts = [
        'units' => 'decimal:2',
        'planned_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'cost' => 'decimal:3',
    ];

    // العلاقات
    public function task(): BelongsTo
    {
        return $this->belongsTo(GanttTask::class, 'task_id');
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(GanttResource::class, 'resource_id');
    }

    // Methods
    /**
     * حساب التكلفة
     */
    public function calculateCost(): float
    {
        $resource = $this->resource;
        $hours = $this->actual_hours ?? $this->planned_hours ?? 0;
        
        if ($resource->cost_per_hour) {
            return $hours * $resource->cost_per_hour * ($this->units / 100);
        }
        
        return 0;
    }
}
