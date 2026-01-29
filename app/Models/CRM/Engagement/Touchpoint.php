<?php

namespace App\Models\CRM\Engagement;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Touchpoint extends Model
{
    protected $fillable = [
        'customer_id',
        'touchpoint_type',
        'channel',
        'source',
        'campaign',
        'data',
        'touched_at',
    ];

    protected $casts = [
        'data' => 'array',
        'touched_at' => 'datetime',
    ];

    // العلاقات
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('touchpoint_type', $type);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('touched_at', '>=', now()->subDays($days));
    }

    public function scopeFromCampaign($query, string $campaign)
    {
        return $query->where('campaign', $campaign);
    }

    // Methods
    public static function recordTouchpoint(
        int $customerId,
        string $type,
        string $channel,
        ?string $source = null,
        ?string $campaign = null,
        ?array $data = null
    ): self {
        return static::create([
            'customer_id' => $customerId,
            'touchpoint_type' => $type,
            'channel' => $channel,
            'source' => $source,
            'campaign' => $campaign,
            'data' => $data,
            'touched_at' => now(),
        ]);
    }

    // إحصائيات
    public static function getCustomerJourney(int $customerId): array
    {
        return static::forCustomer($customerId)
            ->orderBy('touched_at')
            ->get()
            ->map(fn ($tp) => [
                'type' => $tp->touchpoint_type,
                'channel' => $tp->channel,
                'source' => $tp->source,
                'campaign' => $tp->campaign,
                'at' => $tp->touched_at->toDateTimeString(),
            ])
            ->toArray();
    }

    public static function getChannelDistribution(int $days = 30): array
    {
        return static::recent($days)
            ->selectRaw('channel, COUNT(*) as count')
            ->groupBy('channel')
            ->pluck('count', 'channel')
            ->toArray();
    }

    public static function getCampaignPerformance(string $campaign): array
    {
        $touchpoints = static::fromCampaign($campaign)->get();
        
        return [
            'total_touchpoints' => $touchpoints->count(),
            'unique_customers' => $touchpoints->unique('customer_id')->count(),
            'channels' => $touchpoints->countBy('channel')->toArray(),
            'types' => $touchpoints->countBy('touchpoint_type')->toArray(),
        ];
    }
}
