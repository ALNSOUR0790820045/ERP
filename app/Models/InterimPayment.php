<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterimPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'project_id', 'ipc_number', 'period_from', 'period_to',
        'valuation_date', 'gross_value', 'previous_gross', 'current_gross',
        'advance_recovery', 'retention', 'other_deductions', 'net_value',
        'vat_amount', 'total_payable', 'currency_id',
        'status', 'submitted_date', 'certified_date', 'payment_due_date',
        'payment_date', 'certified_by', 'approved_by', 'notes',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'valuation_date' => 'date',
        'submitted_date' => 'date',
        'certified_date' => 'date',
        'payment_due_date' => 'date',
        'payment_date' => 'date',
        'gross_value' => 'decimal:3',
        'previous_gross' => 'decimal:3',
        'current_gross' => 'decimal:3',
        'advance_recovery' => 'decimal:3',
        'retention' => 'decimal:3',
        'other_deductions' => 'decimal:3',
        'net_value' => 'decimal:3',
        'vat_amount' => 'decimal:3',
        'total_payable' => 'decimal:3',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function certifier(): BelongsTo { return $this->belongsTo(User::class, 'certified_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeCertified($query) { return $query->where('status', 'certified'); }
    public function scopePaid($query) { return $query->where('status', 'paid'); }
}
