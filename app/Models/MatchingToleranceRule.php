<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchingToleranceRule extends Model
{
    protected $fillable = [
        'name',
        'name_ar',
        'rule_type',
        'tolerance_percentage',
        'tolerance_amount',
        'min_amount',
        'max_amount',
        'auto_approve_within_tolerance',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'tolerance_percentage' => 'decimal:2',
        'tolerance_amount' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'auto_approve_within_tolerance' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('priority', 'desc');
    }

    public static function getApplicableRule(float $amount): ?self
    {
        return static::active()
            ->where(function ($q) use ($amount) {
                $q->whereNull('min_amount')->orWhere('min_amount', '<=', $amount);
            })
            ->where(function ($q) use ($amount) {
                $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount);
            })
            ->first();
    }
}
