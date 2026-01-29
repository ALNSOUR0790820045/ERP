<?php

namespace App\Models\CRM\Service;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceContract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contract_number',
        'customer_id',
        'contract_name',
        'contract_type',
        'start_date',
        'end_date',
        'contract_value',
        'currency',
        'sla_policy_id',
        'included_hours',
        'used_hours',
        'included_cases',
        'used_cases',
        'covered_products',
        'excluded_services',
        'status',
        'auto_renew',
        'renewal_notice_days',
        'terms_conditions',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'contract_value' => 'decimal:2',
        'included_hours' => 'integer',
        'used_hours' => 'integer',
        'included_cases' => 'integer',
        'used_cases' => 'integer',
        'covered_products' => 'array',
        'excluded_services' => 'array',
        'auto_renew' => 'boolean',
        'renewal_notice_days' => 'integer',
    ];

    // Boot
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($contract) {
            if (empty($contract->contract_number)) {
                $contract->contract_number = 'SC-' . date('Y') . '-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    // العلاقات
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(ServiceCase::class, 'service_contract_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // Methods
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->start_date <= now() && 
               $this->end_date >= now();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->isActive() && 
               $this->end_date->diffInDays(now()) <= $days;
    }

    public function getRemainingHours(): ?int
    {
        if ($this->included_hours === null) return null;
        return max(0, $this->included_hours - $this->used_hours);
    }

    public function getRemainingCases(): ?int
    {
        if ($this->included_cases === null) return null;
        return max(0, $this->included_cases - $this->used_cases);
    }

    public function canCreateCase(): bool
    {
        if (!$this->isActive()) return false;
        
        // التحقق من عدد الحالات المسموحة
        if ($this->included_cases !== null && $this->getRemainingCases() <= 0) {
            return false;
        }
        
        return true;
    }

    public function useHours(int $hours): void
    {
        $this->increment('used_hours', $hours);
    }

    public function useCase(): void
    {
        $this->increment('used_cases');
    }

    public function renew(int $months = 12): self
    {
        return static::create([
            'customer_id' => $this->customer_id,
            'contract_name' => $this->contract_name . ' (Renewed)',
            'contract_type' => $this->contract_type,
            'start_date' => $this->end_date->addDay(),
            'end_date' => $this->end_date->addDay()->addMonths($months),
            'contract_value' => $this->contract_value,
            'currency' => $this->currency,
            'sla_policy_id' => $this->sla_policy_id,
            'included_hours' => $this->included_hours,
            'included_cases' => $this->included_cases,
            'covered_products' => $this->covered_products,
            'excluded_services' => $this->excluded_services,
            'auto_renew' => $this->auto_renew,
            'renewal_notice_days' => $this->renewal_notice_days,
            'terms_conditions' => $this->terms_conditions,
            'created_by' => auth()->id(),
            'status' => 'active',
        ]);
    }
}
