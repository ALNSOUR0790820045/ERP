<?php

namespace App\Models\CRM\Commission;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionPlan extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description',
        'plan_type',
        'effective_from',
        'effective_until',
        'calculation_basis',
        'payment_frequency',
        'is_active',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_until' => 'date',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function rules(): HasMany
    {
        return $this->hasMany(CommissionRule::class, 'plan_id');
    }

    public function calculations(): HasMany
    {
        return $this->hasMany(CommissionCalculation::class, 'plan_id');
    }

    public function userPlans(): HasMany
    {
        return $this->hasMany(UserCommissionPlan::class, 'plan_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', now());
            });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('plan_type', $type);
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    // Methods
    public function isActive(): bool
    {
        if (!$this->is_active) return false;
        if ($this->effective_from > now()) return false;
        if ($this->effective_until && $this->effective_until < now()) return false;
        return true;
    }

    public function calculateCommission(float $amount): float
    {
        return match($this->plan_type) {
            'percentage' => $this->calculatePercentage($amount),
            'fixed' => $this->calculateFixed($amount),
            'tiered' => $this->calculateTiered($amount),
            'hybrid' => $this->calculateHybrid($amount),
            default => 0,
        };
    }

    private function calculatePercentage(float $amount): float
    {
        $rule = $this->rules()->active()->first();
        return $rule ? $amount * ($rule->commission_rate / 100) : 0;
    }

    private function calculateFixed(float $amount): float
    {
        $rule = $this->rules()->active()->first();
        return $rule?->fixed_amount ?? 0;
    }

    private function calculateTiered(float $amount): float
    {
        $commission = 0;
        $remainingAmount = $amount;
        
        $rules = $this->rules()
            ->active()
            ->orderBy('tier_number')
            ->get();
        
        foreach ($rules as $rule) {
            if ($remainingAmount <= 0) break;
            
            $tierAmount = $rule->max_amount 
                ? min($remainingAmount, $rule->max_amount - $rule->min_amount)
                : $remainingAmount;
            
            if ($tierAmount > 0) {
                $commission += $tierAmount * ($rule->commission_rate / 100);
                $remainingAmount -= $tierAmount;
            }
        }
        
        return $commission;
    }

    private function calculateHybrid(float $amount): float
    {
        $rule = $this->rules()->active()->first();
        if (!$rule) return 0;
        
        $baseCommission = $rule->fixed_amount ?? 0;
        $percentageCommission = $amount * (($rule->commission_rate ?? 0) / 100);
        
        return $baseCommission + $percentageCommission;
    }

    public function calculateWithAccelerator(float $amount, float $quotaAchievement): float
    {
        $baseCommission = $this->calculateCommission($amount);
        
        $rule = $this->rules()->active()->first();
        if (!$rule || !$rule->accelerator_rate || !$rule->accelerator_threshold) {
            return $baseCommission;
        }
        
        if ($quotaAchievement >= $rule->accelerator_threshold) {
            $acceleratorMultiplier = 1 + ($rule->accelerator_rate / 100);
            return $baseCommission * $acceleratorMultiplier;
        }
        
        return $baseCommission;
    }
}
