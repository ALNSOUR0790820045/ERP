<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * توزيع القيمة المخططة الزمني
 * Planned Value Distribution over time
 */
class PlannedValueDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'baseline_id',
        'activity_id',
        'period_date',
        'period_type',
        'planned_value',
        'cumulative_pv',
        'planned_percentage',
    ];

    protected $casts = [
        'period_date' => 'date',
        'planned_value' => 'decimal:3',
        'cumulative_pv' => 'decimal:3',
        'planned_percentage' => 'decimal:4',
    ];

    // العلاقات
    public function baseline(): BelongsTo
    {
        return $this->belongsTo(ProjectBaseline::class, 'baseline_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(BaselineActivity::class, 'activity_id');
    }
}
