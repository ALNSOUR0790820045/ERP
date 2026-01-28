<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditLimitChange extends Model
{
    protected $fillable = [
        'customer_credit_profile_id',
        'previous_limit',
        'new_limit',
        'change_reason',
        'change_type',
        'changed_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'previous_limit' => 'decimal:2',
        'new_limit' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function creditProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerCreditProfile::class, 'customer_credit_profile_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getChangeAmountAttribute(): float
    {
        return $this->new_limit - $this->previous_limit;
    }

    public function getChangePercentageAttribute(): float
    {
        if ($this->previous_limit == 0) return 100;
        return (($this->new_limit - $this->previous_limit) / $this->previous_limit) * 100;
    }
}
