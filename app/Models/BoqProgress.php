<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoqProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'boq_item_id', 'interim_payment_id', 'progress_certificate_id',
        'period_date', 'previous_quantity', 'current_quantity', 'cumulative_quantity',
        'previous_amount', 'current_amount', 'cumulative_amount',
        'percentage_complete', 'notes',
    ];

    protected $casts = [
        'period_date' => 'date',
        'previous_quantity' => 'decimal:4',
        'current_quantity' => 'decimal:4',
        'cumulative_quantity' => 'decimal:4',
        'previous_amount' => 'decimal:3',
        'current_amount' => 'decimal:3',
        'cumulative_amount' => 'decimal:3',
        'percentage_complete' => 'decimal:2',
    ];

    public function boqItem(): BelongsTo { return $this->belongsTo(BoqItem::class); }
    public function interimPayment(): BelongsTo { return $this->belongsTo(InterimPayment::class); }
    public function progressCertificate(): BelongsTo { return $this->belongsTo(ProgressCertificate::class); }
}
