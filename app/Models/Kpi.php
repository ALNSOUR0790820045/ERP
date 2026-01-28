<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kpi extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'name_en',
        'description',
        'category',
        'unit',
        'data_source',
        'calculation_formula',
        'target_value',
        'warning_threshold',
        'critical_threshold',
        'comparison_type',
        'frequency',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'target_value' => 'decimal:4',
        'warning_threshold' => 'decimal:4',
        'critical_threshold' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(KpiValue::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(KpiAlert::class);
    }

    public function getLatestValue(): ?KpiValue
    {
        return $this->values()->latest('period_date')->first();
    }

    public function getCategoryNameAttribute(): string
    {
        return match($this->category) {
            'financial' => 'مالي',
            'operational' => 'تشغيلي',
            'quality' => 'الجودة',
            'safety' => 'السلامة',
            'hr' => 'الموارد البشرية',
            'customer' => 'العملاء',
            'project' => 'المشاريع',
            'procurement' => 'المشتريات',
            default => $this->category,
        };
    }
}
