<?php

namespace App\Models\CRM\Commission;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionRule extends Model
{
    protected $fillable = [
        'plan_id',
        'name',
        'tier_number',
        'min_amount',
        'max_amount',
        'commission_rate',
        'fixed_amount',
        'conditions',
        'accelerator_rate',
        'accelerator_threshold',
        'is_active',
    ];

    protected $casts = [
        'tier_number' => 'integer',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'commission_rate' => 'decimal:4',
        'fixed_amount' => 'decimal:2',
        'conditions' => 'array',
        'accelerator_rate' => 'decimal:4',
        'accelerator_threshold' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function plan(): BelongsTo
    {
        return $this->belongsTo(CommissionPlan::class, 'plan_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTier($query, int $tier)
    {
        return $query->where('tier_number', $tier);
    }

    // Methods
    public function matchesAmount(float $amount): bool
    {
        if ($amount < $this->min_amount) return false;
        if ($this->max_amount && $amount > $this->max_amount) return false;
        return true;
    }

    public function matchesConditions(array $context): bool
    {
        if (empty($this->conditions)) return true;
        
        foreach ($this->conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;
            $contextValue = $context[$field] ?? null;
            
            $matches = match($operator) {
                'equals' => $contextValue == $value,
                'not_equals' => $contextValue != $value,
                'greater_than' => $contextValue > $value,
                'less_than' => $contextValue < $value,
                'in' => in_array($contextValue, (array) $value),
                default => true,
            };
            
            if (!$matches) return false;
        }
        
        return true;
    }

    public function calculateCommission(float $amount): float
    {
        if ($this->fixed_amount) {
            return $this->fixed_amount;
        }
        
        return $amount * (($this->commission_rate ?? 0) / 100);
    }

    public function hasAccelerator(): bool
    {
        return $this->accelerator_rate && $this->accelerator_threshold;
    }
}
