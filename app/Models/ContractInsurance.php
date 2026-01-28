<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractInsurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'insurance_type',
        'policy_number',
        'insurer',
        'coverage_amount',
        'premium',
        'currency_id',
        'start_date',
        'expiry_date',
        'deductible',
        'status',
        'coverage_details',
        'document_path',
    ];

    protected $casts = [
        'coverage_amount' => 'decimal:3',
        'premium' => 'decimal:3',
        'deductible' => 'decimal:3',
        'start_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && 
               $this->start_date->lte(now()) && 
               $this->expiry_date->gte(now());
    }

    public function getDaysToExpiryAttribute(): int
    {
        return now()->diffInDays($this->expiry_date, false);
    }
}
