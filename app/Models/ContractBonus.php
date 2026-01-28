<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractBonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'bonus_type', 'description', 'calculation_method',
        'rate', 'max_percentage', 'max_amount', 'trigger_condition',
        'achievement_date', 'days_early', 'calculated_amount',
        'approved_amount', 'status', 'approved_by', 'approved_at', 'notes',
    ];

    protected $casts = [
        'achievement_date' => 'date',
        'approved_at' => 'datetime',
        'rate' => 'decimal:4',
        'max_percentage' => 'decimal:2',
        'max_amount' => 'decimal:3',
        'calculated_amount' => 'decimal:3',
        'approved_amount' => 'decimal:3',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopePending($query) { return $query->where('status', 'pending'); }
}
