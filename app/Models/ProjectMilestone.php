<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMilestone extends Model
{
    protected $fillable = [
        'project_id',
        'wbs_id',
        'name',
        'description',
        'planned_date',
        'actual_date',
        'weight',
        'is_payment_milestone',
        'payment_percentage',
        'status',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'actual_date' => 'date',
        'weight' => 'decimal:2',
        'is_payment_milestone' => 'boolean',
        'payment_percentage' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function wbs(): BelongsTo
    {
        return $this->belongsTo(ProjectWbs::class, 'wbs_id');
    }

    public function getIsDelayedAttribute(): bool
    {
        return $this->status !== 'achieved' && $this->planned_date < now();
    }

    public function getDelayDaysAttribute(): int
    {
        if ($this->actual_date && $this->planned_date) {
            return $this->planned_date->diffInDays($this->actual_date, false);
        }
        if ($this->planned_date < now() && $this->status !== 'achieved') {
            return $this->planned_date->diffInDays(now());
        }
        return 0;
    }

    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            'pending' => 'قيد الانتظار',
            'achieved' => 'محقق',
            'delayed' => 'متأخر',
            'cancelled' => 'ملغى',
            default => $this->status,
        };
    }
}
