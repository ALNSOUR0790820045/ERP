<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingCampaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'campaign_code',
        'name',
        'type',
        'start_date',
        'end_date',
        'budget',
        'actual_cost',
        'target_leads',
        'actual_leads',
        'status',
        'description',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRoiAttribute(): ?float
    {
        if (!$this->actual_cost || $this->actual_cost <= 0) return null;
        // Assuming average lead value, simplified calculation
        return round(($this->actual_leads * 1000 - $this->actual_cost) / $this->actual_cost * 100, 2);
    }

    public function getConversionRateAttribute(): ?float
    {
        if (!$this->target_leads || $this->target_leads <= 0) return null;
        return round(($this->actual_leads / $this->target_leads) * 100, 2);
    }

    public static function generateCode(): string
    {
        $year = date('Y');
        $last = static::whereYear('created_at', $year)->count() + 1;
        return "CAM-{$year}-" . str_pad($last, 4, '0', STR_PAD_LEFT);
    }
}
