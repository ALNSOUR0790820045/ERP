<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectResourceAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'wbs_id',
        'resource_id',
        'planned_quantity',
        'actual_quantity',
        'unit_rate',
        'planned_cost',
        'actual_cost',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:4',
        'actual_quantity' => 'decimal:4',
        'unit_rate' => 'decimal:3',
        'planned_cost' => 'decimal:3',
        'actual_cost' => 'decimal:3',
    ];

    public function wbs(): BelongsTo
    {
        return $this->belongsTo(ProjectWbs::class, 'wbs_id');
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(ProjectResource::class, 'resource_id');
    }
}
