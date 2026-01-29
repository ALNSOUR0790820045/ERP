<?php

namespace App\Models\CRM\Sales;

use App\Models\User;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\CRM\Lead\Lead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineDeal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'deal_number',
        'deal_name',
        'pipeline_id',
        'stage_id',
        'customer_id',
        'opportunity_id',
        'lead_id',
        'deal_value',
        'currency',
        'probability',
        'weighted_value',
        'expected_close_date',
        'actual_close_date',
        'status',
        'lost_reason',
        'competitor_id',
        'assigned_to',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'deal_value' => 'decimal:2',
        'probability' => 'integer',
        'weighted_value' => 'decimal:2',
        'expected_close_date' => 'date',
        'actual_close_date' => 'date',
    ];

    // Boot
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($deal) {
            if (empty($deal->deal_number)) {
                $deal->deal_number = 'DL-' . date('Y') . '-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }
            $deal->weighted_value = $deal->deal_value * ($deal->probability / 100);
        });
        
        static::updating(function ($deal) {
            if ($deal->isDirty(['deal_value', 'probability'])) {
                $deal->weighted_value = $deal->deal_value * ($deal->probability / 100);
            }
        });
    }

    // العلاقات
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(SalesPipeline::class, 'pipeline_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(SalesStage::class, 'stage_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeWon($query)
    {
        return $query->where('status', 'won');
    }

    public function scopeLost($query)
    {
        return $query->where('status', 'lost');
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeClosingThisMonth($query)
    {
        return $query->open()
            ->whereMonth('expected_close_date', now()->month)
            ->whereYear('expected_close_date', now()->year);
    }

    // Methods
    public function moveToStage(int $stageId): void
    {
        $stage = SalesStage::find($stageId);
        
        $this->update([
            'stage_id' => $stageId,
            'probability' => $stage->default_probability ?? $this->probability,
        ]);
    }

    public function markAsWon(): void
    {
        $wonStage = SalesStage::where('is_won_stage', true)->first();
        
        $this->update([
            'status' => 'won',
            'stage_id' => $wonStage?->id ?? $this->stage_id,
            'probability' => 100,
            'actual_close_date' => now(),
        ]);
    }

    public function markAsLost(string $reason): void
    {
        $lostStage = SalesStage::where('is_lost_stage', true)->first();
        
        $this->update([
            'status' => 'lost',
            'stage_id' => $lostStage?->id ?? $this->stage_id,
            'probability' => 0,
            'lost_reason' => $reason,
            'actual_close_date' => now(),
        ]);
    }

    public function getAgeInDays(): int
    {
        return $this->created_at->diffInDays(now());
    }

    public function isOverdue(): bool
    {
        return $this->status === 'open' && 
               $this->expected_close_date && 
               $this->expected_close_date->isPast();
    }
}
