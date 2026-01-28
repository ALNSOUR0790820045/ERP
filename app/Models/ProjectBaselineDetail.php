<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBaselineDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'baseline_id',
        'wbs_id',
        'planned_start',
        'planned_finish',
        'duration_days',
        'budget',
    ];

    protected $casts = [
        'planned_start' => 'date',
        'planned_finish' => 'date',
        'budget' => 'decimal:3',
    ];

    public function baseline(): BelongsTo
    {
        return $this->belongsTo(ProjectBaseline::class, 'baseline_id');
    }

    public function wbs(): BelongsTo
    {
        return $this->belongsTo(ProjectWbs::class, 'wbs_id');
    }
}
