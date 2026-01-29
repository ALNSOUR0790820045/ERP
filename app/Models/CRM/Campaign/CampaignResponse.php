<?php

namespace App\Models\CRM\Campaign;

use App\Models\Customer;
use App\Models\CRM\Lead\Lead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignResponse extends Model
{
    protected $fillable = [
        'campaign_id',
        'target_id',
        'customer_id',
        'lead_id',
        'response_type',
        'link_clicked',
        'metadata',
        'responded_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'responded_at' => 'datetime',
    ];

    // العلاقات
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(\App\Models\MarketingCampaign::class, 'campaign_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(CampaignTarget::class, 'target_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    // Scopes
    public function scopeForCampaign($query, int $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('response_type', $type);
    }

    public function scopeOpens($query)
    {
        return $query->where('response_type', 'open');
    }

    public function scopeClicks($query)
    {
        return $query->where('response_type', 'click');
    }

    public function scopeConversions($query)
    {
        return $query->where('response_type', 'conversion');
    }

    public function scopeUnsubscribes($query)
    {
        return $query->where('response_type', 'unsubscribe');
    }

    public function scopeBounces($query)
    {
        return $query->where('response_type', 'bounce');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('responded_at', '>=', now()->subDays($days));
    }

    // Methods
    public static function recordOpen(int $campaignId, ?int $targetId, ?int $customerId, ?int $leadId): self
    {
        return static::create([
            'campaign_id' => $campaignId,
            'target_id' => $targetId,
            'customer_id' => $customerId,
            'lead_id' => $leadId,
            'response_type' => 'open',
            'responded_at' => now(),
        ]);
    }

    public static function recordClick(int $campaignId, ?int $targetId, ?int $customerId, ?int $leadId, ?string $link = null): self
    {
        return static::create([
            'campaign_id' => $campaignId,
            'target_id' => $targetId,
            'customer_id' => $customerId,
            'lead_id' => $leadId,
            'response_type' => 'click',
            'link_clicked' => $link,
            'responded_at' => now(),
        ]);
    }

    public static function recordConversion(int $campaignId, ?int $targetId, ?int $customerId, ?int $leadId, ?array $metadata = null): self
    {
        return static::create([
            'campaign_id' => $campaignId,
            'target_id' => $targetId,
            'customer_id' => $customerId,
            'lead_id' => $leadId,
            'response_type' => 'conversion',
            'metadata' => $metadata,
            'responded_at' => now(),
        ]);
    }

    // إحصائيات
    public static function getCampaignStats(int $campaignId): array
    {
        $responses = static::forCampaign($campaignId)->get();
        $targets = CampaignTarget::forCampaign($campaignId)->count();
        $sent = CampaignTarget::forCampaign($campaignId)->sent()->count();
        
        $opens = $responses->where('response_type', 'open')->count();
        $clicks = $responses->where('response_type', 'click')->count();
        $conversions = $responses->where('response_type', 'conversion')->count();
        $unsubscribes = $responses->where('response_type', 'unsubscribe')->count();
        $bounces = $responses->where('response_type', 'bounce')->count();
        
        return [
            'total_targets' => $targets,
            'sent' => $sent,
            'opens' => $opens,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'unsubscribes' => $unsubscribes,
            'bounces' => $bounces,
            'open_rate' => $sent > 0 ? ($opens / $sent) * 100 : 0,
            'click_rate' => $opens > 0 ? ($clicks / $opens) * 100 : 0,
            'conversion_rate' => $clicks > 0 ? ($conversions / $clicks) * 100 : 0,
            'bounce_rate' => $sent > 0 ? ($bounces / $sent) * 100 : 0,
        ];
    }
}
