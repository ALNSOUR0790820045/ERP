<?php

namespace App\Models\CRM\Commission;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCommissionPlan extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'effective_from',
        'effective_until',
        'custom_rate',
        'is_active',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_until' => 'date',
        'custom_rate' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CommissionPlan::class, 'plan_id');
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

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPlan($query, int $planId)
    {
        return $query->where('plan_id', $planId);
    }

    // Methods
    public function isActive(): bool
    {
        if (!$this->is_active) return false;
        if ($this->effective_from > now()) return false;
        if ($this->effective_until && $this->effective_until < now()) return false;
        return true;
    }

    public function getEffectiveRate(): ?float
    {
        return $this->custom_rate;
    }

    public function terminate(): void
    {
        $this->update([
            'effective_until' => now(),
            'is_active' => false,
        ]);
    }

    public static function assignPlan(int $userId, int $planId, ?float $customRate = null): self
    {
        // إنهاء الخطة الحالية
        static::forUser($userId)->active()->each(fn ($up) => $up->terminate());
        
        return static::create([
            'user_id' => $userId,
            'plan_id' => $planId,
            'effective_from' => now(),
            'custom_rate' => $customRate,
            'is_active' => true,
        ]);
    }
}
