<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'claim_number',
        'claim_type',
        'title',
        'description',
        'clause_reference',
        'event_date',
        'notice_date',
        'submission_date',
        'notice_compliant',
        'time_claimed_days',
        'cost_claimed',
        'loss_profit_claimed',
        'total_claimed',
        'time_approved_days',
        'cost_approved',
        'total_approved',
        'status',
        'reviewed_by',
        'review_date',
        'review_notes',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'notice_date' => 'date',
        'submission_date' => 'date',
        'review_date' => 'date',
        'notice_compliant' => 'boolean',
        'cost_claimed' => 'decimal:3',
        'loss_profit_claimed' => 'decimal:3',
        'total_claimed' => 'decimal:3',
        'cost_approved' => 'decimal:3',
        'total_approved' => 'decimal:3',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ContractClaimDocument::class, 'claim_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getApprovalRateAttribute(): float
    {
        if ($this->total_claimed <= 0) {
            return 0;
        }
        return round(($this->total_approved / $this->total_claimed) * 100, 2);
    }
}
