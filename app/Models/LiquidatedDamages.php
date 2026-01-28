<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidatedDamages extends Model
{
    use HasFactory;

    protected $table = 'liquidated_damages';

    protected $fillable = [
        'contract_id', 'project_id', 'calculation_date', 'contract_completion_date',
        'actual_completion_date', 'delay_days', 'excusable_delay_days',
        'chargeable_delay_days', 'daily_rate', 'rate_type', 'max_percentage',
        'max_amount', 'calculated_amount', 'capped_amount', 'applied_amount',
        'waived_amount', 'waiver_reason', 'status',
        'calculated_by', 'approved_by', 'approved_at', 'notes',
    ];

    protected $casts = [
        'calculation_date' => 'date',
        'contract_completion_date' => 'date',
        'actual_completion_date' => 'date',
        'approved_at' => 'datetime',
        'daily_rate' => 'decimal:3',
        'max_percentage' => 'decimal:2',
        'max_amount' => 'decimal:3',
        'calculated_amount' => 'decimal:3',
        'capped_amount' => 'decimal:3',
        'applied_amount' => 'decimal:3',
        'waived_amount' => 'decimal:3',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function calculator(): BelongsTo { return $this->belongsTo(User::class, 'calculated_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeApplied($query) { return $query->where('status', 'applied'); }
    public function scopeWaived($query) { return $query->where('status', 'waived'); }
}
