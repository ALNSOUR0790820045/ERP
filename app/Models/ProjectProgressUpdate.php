<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectProgressUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'wbs_id',
        'update_date',
        'data_date',
        'planned_progress',
        'actual_progress',
        'remaining_duration',
        'notes',
        'updated_by',
    ];

    protected $casts = [
        'update_date' => 'date',
        'data_date' => 'date',
        'planned_progress' => 'decimal:2',
        'actual_progress' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function wbs(): BelongsTo
    {
        return $this->belongsTo(ProjectWbs::class, 'wbs_id');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
