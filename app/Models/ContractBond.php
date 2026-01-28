<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractBond extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'bond_type',
        'bond_number',
        'issuer',
        'amount',
        'currency_id',
        'issue_date',
        'validity_date',
        'expiry_date',
        'status',
        'release_date',
        'notes',
        'document_path',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'issue_date' => 'date',
        'validity_date' => 'date',
        'expiry_date' => 'date',
        'release_date' => 'date',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getDaysToExpiryAttribute(): int
    {
        if (!$this->expiry_date) {
            return 0;
        }
        return now()->diffInDays($this->expiry_date, false);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'active')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }
}
