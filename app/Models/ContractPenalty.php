<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractPenalty extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'penalty_type', 'description', 'calculation_method',
        'rate', 'max_percentage', 'max_amount', 'occurrence_date',
        'delay_days', 'calculated_amount', 'applied_amount',
        'waived_amount', 'waiver_reason', 'status',
        'applied_by', 'applied_at', 'notes',
    ];

    protected $casts = [
        'occurrence_date' => 'date',
        'applied_at' => 'datetime',
        'rate' => 'decimal:4',
        'max_percentage' => 'decimal:2',
        'max_amount' => 'decimal:3',
        'calculated_amount' => 'decimal:3',
        'applied_amount' => 'decimal:3',
        'waived_amount' => 'decimal:3',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function appliedBy(): BelongsTo { return $this->belongsTo(User::class, 'applied_by'); }

    public function scopeApplied($query) { return $query->where('status', 'applied'); }
    public function scopeWaived($query) { return $query->where('status', 'waived'); }
}
