<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatifAssumption extends Model
{
    protected $fillable = [
        'whatif_scenario_id',
        'assumption_type',
        'affected_entity_type',
        'affected_entity_id',
        'affected_entity_name',
        'parameter_name',
        'original_value',
        'assumed_value',
        'value_unit',
        'change_percentage',
        'probability',
        'justification',
    ];

    protected $casts = [
        'original_value' => 'decimal:2',
        'assumed_value' => 'decimal:2',
        'change_percentage' => 'decimal:2',
        'probability' => 'decimal:2',
    ];

    public static array $assumptionTypes = [
        'activity_delay' => 'تأخير نشاط',
        'activity_acceleration' => 'تسريع نشاط',
        'cost_increase' => 'زيادة تكلفة',
        'cost_decrease' => 'تخفيض تكلفة',
        'resource_unavailable' => 'عدم توفر مورد',
        'resource_added' => 'إضافة مورد',
        'scope_change' => 'تغيير نطاق',
        'risk_occurrence' => 'حدوث خطر',
        'weather_delay' => 'تأخير طقس',
        'custom' => 'مخصص',
    ];

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(WhatifScenario::class, 'whatif_scenario_id');
    }

    public function getImpactValueAttribute(): float
    {
        return ($this->assumed_value - $this->original_value) * ($this->probability / 100);
    }
}
