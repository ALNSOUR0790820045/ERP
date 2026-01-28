<?php

namespace App\Models;

use App\Enums\WbsType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectWbs extends Model
{
    use HasFactory;

    protected $table = 'project_wbs';

    protected $fillable = [
        'project_id',
        'parent_id',
        'wbs_code',
        'name_ar',
        'name_en',
        'description',
        'type',
        'level',
        'planned_start',
        'planned_finish',
        'actual_start',
        'actual_finish',
        'duration_days',
        'planned_progress',
        'actual_progress',
        'weight',
        'budget',
        'actual_cost',
        'early_start',
        'early_finish',
        'late_start',
        'late_finish',
        'total_float',
        'free_float',
        'is_critical',
        'constraint_type',
        'constraint_date',
        'sort_order',
    ];

    protected $casts = [
        'type' => WbsType::class,
        'planned_start' => 'date',
        'planned_finish' => 'date',
        'actual_start' => 'date',
        'actual_finish' => 'date',
        'constraint_date' => 'date',
        'planned_progress' => 'decimal:2',
        'actual_progress' => 'decimal:2',
        'weight' => 'decimal:4',
        'budget' => 'decimal:3',
        'actual_cost' => 'decimal:3',
        'is_critical' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectWbs::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProjectWbs::class, 'parent_id')->orderBy('sort_order');
    }

    public function resourceAssignments(): HasMany
    {
        return $this->hasMany(ProjectResourceAssignment::class, 'wbs_id');
    }

    public function predecessors(): BelongsToMany
    {
        return $this->belongsToMany(ProjectWbs::class, 'project_wbs_dependencies', 'successor_id', 'predecessor_id')
            ->withPivot(['type', 'lag_days'])
            ->withTimestamps();
    }

    public function successors(): BelongsToMany
    {
        return $this->belongsToMany(ProjectWbs::class, 'project_wbs_dependencies', 'predecessor_id', 'successor_id')
            ->withPivot(['type', 'lag_days'])
            ->withTimestamps();
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?? $this->name_ar);
    }

    public function getProgressVarianceAttribute(): float
    {
        return $this->actual_progress - $this->planned_progress;
    }

    public function getIsDelayedAttribute(): bool
    {
        if (!$this->planned_finish) {
            return false;
        }
        if ($this->actual_finish) {
            return $this->actual_finish->gt($this->planned_finish);
        }
        return now()->gt($this->planned_finish) && $this->actual_progress < 100;
    }
}
