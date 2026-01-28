<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CriticalPath extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'name', 'calculation_date', 'total_float',
        'free_float', 'critical_tasks', 'project_duration',
        'early_start', 'early_finish', 'late_start', 'late_finish',
        'data_date', 'status', 'notes',
    ];

    protected $casts = [
        'calculation_date' => 'date',
        'data_date' => 'date',
        'early_start' => 'date',
        'early_finish' => 'date',
        'late_start' => 'date',
        'late_finish' => 'date',
        'total_float' => 'decimal:2',
        'free_float' => 'decimal:2',
        'critical_tasks' => 'array',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
}
