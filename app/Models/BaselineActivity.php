<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * نشاط خط الأساس
 * Baseline Activity for EVM
 */
class BaselineActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'baseline_id',
        'activity_code',
        'name',
        'description',
        'parent_id',
        'planned_start',
        'planned_finish',
        'planned_duration_days',
        'planned_value',
        'weight_percentage',
        'sequence',
        'predecessors',
        'resources',
    ];

    protected $casts = [
        'planned_start' => 'date',
        'planned_finish' => 'date',
        'planned_value' => 'decimal:3',
        'weight_percentage' => 'decimal:4',
        'predecessors' => 'array',
        'resources' => 'array',
    ];

    // العلاقات
    public function baseline(): BelongsTo
    {
        return $this->belongsTo(ProjectBaseline::class, 'baseline_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(BaselineActivity::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(BaselineActivity::class, 'parent_id');
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(PlannedValueDistribution::class, 'activity_id');
    }

    public function measurementDetails(): HasMany
    {
        return $this->hasMany(EvmMeasurementDetail::class, 'activity_id');
    }

    // Methods
    /**
     * حساب وزن النشاط من إجمالي خط الأساس
     */
    public function calculateWeight(): float
    {
        $baseline = $this->baseline;
        if (!$baseline || $baseline->budget_at_completion == 0) {
            return 0;
        }
        
        return ($this->planned_value / $baseline->budget_at_completion) * 100;
    }
}
