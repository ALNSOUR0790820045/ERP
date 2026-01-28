<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvanceRecovery extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'interim_payment_id', 'progress_certificate_id',
        'advance_payment_id', 'recovery_type', 'recovery_percentage',
        'gross_value', 'previous_recovery', 'current_recovery',
        'cumulative_recovery', 'advance_amount', 'balance_remaining',
        'fully_recovered', 'notes',
    ];

    protected $casts = [
        'recovery_percentage' => 'decimal:2',
        'gross_value' => 'decimal:3',
        'previous_recovery' => 'decimal:3',
        'current_recovery' => 'decimal:3',
        'cumulative_recovery' => 'decimal:3',
        'advance_amount' => 'decimal:3',
        'balance_remaining' => 'decimal:3',
        'fully_recovered' => 'boolean',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function interimPayment(): BelongsTo { return $this->belongsTo(InterimPayment::class); }
    public function progressCertificate(): BelongsTo { return $this->belongsTo(ProgressCertificate::class); }
    public function advancePayment(): BelongsTo { return $this->belongsTo(ContractAdvancePayment::class, 'advance_payment_id'); }

    public function scopeActive($query) { return $query->where('fully_recovered', false); }
}
