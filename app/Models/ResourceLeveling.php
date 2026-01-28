<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceLeveling extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'leveling_date', 'resource_type', 'resource_id',
        'original_schedule', 'leveled_schedule', 'overallocation_periods',
        'leveling_method', 'priority_rules', 'constraints',
        'impact_on_duration', 'impact_on_cost', 'status', 'notes',
    ];

    protected $casts = [
        'leveling_date' => 'date',
        'original_schedule' => 'array',
        'leveled_schedule' => 'array',
        'overallocation_periods' => 'array',
        'priority_rules' => 'array',
        'constraints' => 'array',
        'impact_on_cost' => 'decimal:3',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
}
