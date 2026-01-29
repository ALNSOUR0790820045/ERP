<?php

namespace App\Models\CRM\Engagement;

use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerContact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomerInteraction extends Model
{
    protected $fillable = [
        'customer_id',
        'contact_id',
        'interaction_type',
        'direction',
        'subject',
        'summary',
        'duration_minutes',
        'related_type',
        'related_id',
        'outcome',
        'interaction_at',
        'user_id',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'interaction_at' => 'datetime',
    ];

    // العلاقات
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CustomerContact::class, 'contact_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo('related', 'related_type', 'related_id');
    }

    // Scopes
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('interaction_type', $type);
    }

    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('interaction_at', '>=', now()->subDays($days));
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('outcome', 'successful');
    }

    // Methods
    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    public function isOutbound(): bool
    {
        return $this->direction === 'outbound';
    }

    public function getDurationFormatted(): string
    {
        if (!$this->duration_minutes) return '-';
        
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        if ($hours > 0) {
            return "{$hours}:{$minutes} ساعة";
        }
        
        return "{$minutes} دقيقة";
    }

    // إحصائيات
    public static function getByTypeDistribution(int $customerId, int $days = 90): array
    {
        return static::forCustomer($customerId)
            ->recent($days)
            ->selectRaw('interaction_type, COUNT(*) as count')
            ->groupBy('interaction_type')
            ->pluck('count', 'interaction_type')
            ->toArray();
    }

    public static function getInteractionFrequency(int $customerId, int $days = 90): float
    {
        $count = static::forCustomer($customerId)->recent($days)->count();
        return $days > 0 ? $count / ($days / 30) : 0; // معدل شهري
    }

    public static function getLastInteraction(int $customerId): ?self
    {
        return static::forCustomer($customerId)
            ->orderByDesc('interaction_at')
            ->first();
    }
}
