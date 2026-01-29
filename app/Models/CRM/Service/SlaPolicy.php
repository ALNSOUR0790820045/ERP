<?php

namespace App\Models\CRM\Service;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlaPolicy extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description',
        'priority',
        'first_response_hours',
        'resolution_hours',
        'escalation_hours',
        'business_hours',
        'exclude_weekends',
        'exclude_holidays',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'first_response_hours' => 'integer',
        'resolution_hours' => 'integer',
        'escalation_hours' => 'integer',
        'business_hours' => 'array',
        'exclude_weekends' => 'boolean',
        'exclude_holidays' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function cases(): HasMany
    {
        return $this->hasMany(ServiceCase::class, 'sla_policy_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(ServiceContract::class, 'sla_policy_id');
    }

    public function breaches(): HasMany
    {
        return $this->hasMany(SlaBreach::class, 'sla_policy_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    // Methods
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    public static function getForPriority(string $priority): ?self
    {
        return static::active()
            ->forPriority($priority)
            ->first() ?? static::getDefault();
    }

    public function calculateFirstResponseDue(\DateTime $createdAt): \DateTime
    {
        return $this->addBusinessHours($createdAt, $this->first_response_hours);
    }

    public function calculateResolutionDue(\DateTime $createdAt): \DateTime
    {
        return $this->addBusinessHours($createdAt, $this->resolution_hours);
    }

    private function addBusinessHours(\DateTime $start, int $hours): \DateTime
    {
        $result = clone $start;
        $remainingHours = $hours;
        
        while ($remainingHours > 0) {
            $result->modify('+1 hour');
            
            if ($this->isBusinessHour($result)) {
                $remainingHours--;
            }
        }
        
        return $result;
    }

    private function isBusinessHour(\DateTime $dateTime): bool
    {
        // تجاوز عطلة نهاية الأسبوع
        if ($this->exclude_weekends && in_array($dateTime->format('N'), [5, 6])) {
            return false;
        }
        
        // التحقق من ساعات العمل
        if ($this->business_hours) {
            $dayName = strtolower($dateTime->format('l'));
            $hour = (int) $dateTime->format('G');
            
            if (isset($this->business_hours[$dayName])) {
                $dayHours = $this->business_hours[$dayName];
                return $hour >= $dayHours['start'] && $hour < $dayHours['end'];
            }
            
            return false;
        }
        
        return true;
    }
}
