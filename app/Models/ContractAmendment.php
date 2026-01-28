<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractAmendment extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'amendment_number', 'amendment_type', 'title',
        'description', 'reason', 'original_value', 'amended_value',
        'value_change', 'original_end_date', 'amended_end_date',
        'time_extension_days', 'affected_clauses', 'effective_date',
        'status', 'requested_by', 'approved_by', 'approved_at', 'notes',
    ];

    protected $casts = [
        'original_end_date' => 'date',
        'amended_end_date' => 'date',
        'effective_date' => 'date',
        'approved_at' => 'datetime',
        'original_value' => 'decimal:3',
        'amended_value' => 'decimal:3',
        'value_change' => 'decimal:3',
        'affected_clauses' => 'array',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopePending($query) { return $query->where('status', 'pending'); }
}
