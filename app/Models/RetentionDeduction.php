<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetentionDeduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'interim_payment_id', 'progress_certificate_id',
        'retention_type', 'retention_percentage', 'gross_value',
        'previous_retention', 'current_retention', 'cumulative_retention',
        'max_retention_percentage', 'max_retention_amount',
        'capped', 'notes',
    ];

    protected $casts = [
        'retention_percentage' => 'decimal:2',
        'gross_value' => 'decimal:3',
        'previous_retention' => 'decimal:3',
        'current_retention' => 'decimal:3',
        'cumulative_retention' => 'decimal:3',
        'max_retention_percentage' => 'decimal:2',
        'max_retention_amount' => 'decimal:3',
        'capped' => 'boolean',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function interimPayment(): BelongsTo { return $this->belongsTo(InterimPayment::class); }
    public function progressCertificate(): BelongsTo { return $this->belongsTo(ProgressCertificate::class); }
}
