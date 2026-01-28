<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractTermination extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'termination_type', 'reason', 'notice_date',
        'effective_date', 'notice_period_days', 'work_completed_percentage',
        'final_valuation', 'amount_paid', 'amount_due', 'penalties',
        'claims', 'settlement_amount', 'settlement_date',
        'status', 'approved_by', 'approved_at', 'notes',
    ];

    protected $casts = [
        'notice_date' => 'date',
        'effective_date' => 'date',
        'settlement_date' => 'date',
        'approved_at' => 'datetime',
        'work_completed_percentage' => 'decimal:2',
        'final_valuation' => 'decimal:3',
        'amount_paid' => 'decimal:3',
        'amount_due' => 'decimal:3',
        'penalties' => 'decimal:3',
        'claims' => 'decimal:3',
        'settlement_amount' => 'decimal:3',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopeSettled($query) { return $query->where('status', 'settled'); }
}
