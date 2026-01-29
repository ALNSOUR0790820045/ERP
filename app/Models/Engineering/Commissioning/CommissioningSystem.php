<?php

namespace App\Models\Engineering\Commissioning;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissioningSystem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'system_number',
        'name_ar',
        'name_en',
        'description',
        'system_type',
        'location',
        'building',
        'floor',
        'parent_system_id',
        'priority',
        'planned_start_date',
        'planned_completion_date',
        'actual_start_date',
        'actual_completion_date',
        'completion_percentage',
        'status',
        'responsible_engineer_id',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_completion_date' => 'date',
        'actual_start_date' => 'date',
        'actual_completion_date' => 'date',
        'completion_percentage' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parentSystem(): BelongsTo
    {
        return $this->belongsTo(CommissioningSystem::class, 'parent_system_id');
    }

    public function childSystems(): HasMany
    {
        return $this->hasMany(CommissioningSystem::class, 'parent_system_id');
    }

    public function responsibleEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_engineer_id');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(CommissioningChecklist::class, 'system_id');
    }

    public function startupProcedures(): HasMany
    {
        return $this->hasMany(StartupProcedure::class, 'system_id');
    }

    public function updateCompletion(): void
    {
        $checklists = $this->checklists;
        if ($checklists->isEmpty()) {
            return;
        }

        $completed = $checklists->where('status', 'completed')->count();
        $total = $checklists->count();
        $this->completion_percentage = $total > 0 ? round(($completed / $total) * 100, 2) : 0;
        $this->save();
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_system_id');
    }

    public function scopeCritical($query)
    {
        return $query->where('priority', 'critical');
    }
}
