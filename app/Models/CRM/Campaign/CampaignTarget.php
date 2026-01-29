<?php

namespace App\Models\CRM\Campaign;

use App\Models\Customer;
use App\Models\CRM\Lead\Lead;
use App\Models\CRM\Engagement\CustomerSegment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignTarget extends Model
{
    protected $fillable = [
        'campaign_id',
        'customer_id',
        'lead_id',
        'segment_id',
        'status',
        'sent_at',
        'opened_at',
        'clicked_at',
        'converted_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    // العلاقات
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(\App\Models\MarketingCampaign::class, 'campaign_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(CustomerSegment::class, 'segment_id');
    }

    // Scopes
    public function scopeForCampaign($query, int $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeOpened($query)
    {
        return $query->whereIn('status', ['opened', 'clicked', 'converted']);
    }

    public function scopeClicked($query)
    {
        return $query->whereIn('status', ['clicked', 'converted']);
    }

    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    // Methods
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsOpened(): void
    {
        if (!$this->opened_at) {
            $this->update([
                'status' => 'opened',
                'opened_at' => now(),
            ]);
        }
    }

    public function markAsClicked(): void
    {
        $this->markAsOpened();
        
        if (!$this->clicked_at) {
            $this->update([
                'status' => 'clicked',
                'clicked_at' => now(),
            ]);
        }
    }

    public function markAsConverted(): void
    {
        $this->markAsClicked();
        
        $this->update([
            'status' => 'converted',
            'converted_at' => now(),
        ]);
    }

    public function unsubscribe(): void
    {
        $this->update(['status' => 'unsubscribed']);
    }

    public function bounce(): void
    {
        $this->update(['status' => 'bounced']);
    }

    public function getRecipientEmail(): ?string
    {
        return $this->customer?->email ?? $this->lead?->email;
    }

    public function getRecipientName(): ?string
    {
        return $this->customer?->company_name ?? $this->lead?->contact_name;
    }
}
