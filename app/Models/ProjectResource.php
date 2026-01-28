<?php

namespace App\Models;

use App\Enums\ResourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'resource_type',
        'name',
        'code',
        'unit_id',
        'hourly_rate',
        'daily_rate',
        'monthly_rate',
        'availability_percentage',
    ];

    protected $casts = [
        'resource_type' => ResourceType::class,
        'hourly_rate' => 'decimal:3',
        'daily_rate' => 'decimal:3',
        'monthly_rate' => 'decimal:3',
        'availability_percentage' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectResourceAssignment::class, 'resource_id');
    }

    public function getTotalPlannedCostAttribute(): float
    {
        return $this->assignments->sum('planned_cost');
    }

    public function getTotalActualCostAttribute(): float
    {
        return $this->assignments->sum('actual_cost');
    }
}
