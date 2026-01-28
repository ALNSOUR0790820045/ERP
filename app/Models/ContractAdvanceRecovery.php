<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractAdvanceRecovery extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_advance_payment_id',
        'progress_certificate_id',
        'recovery_number',
        'recovery_date',
        'ipc_amount',
        'recovery_rate',
        'recovery_amount',
        'balance_before',
        'balance_after',
    ];

    protected $casts = [
        'recovery_date' => 'date',
        'ipc_amount' => 'decimal:3',
        'recovery_rate' => 'decimal:2',
        'recovery_amount' => 'decimal:3',
        'balance_before' => 'decimal:3',
        'balance_after' => 'decimal:3',
    ];

    // العلاقات
    public function advancePayment(): BelongsTo
    {
        return $this->belongsTo(ContractAdvancePayment::class, 'contract_advance_payment_id');
    }

    public function progressCertificate(): BelongsTo
    {
        return $this->belongsTo(ProgressCertificate::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($recovery) {
            $advance = $recovery->advancePayment;
            $recovery->balance_before = $advance->balance_amount;
            $recovery->balance_after = $advance->balance_amount - $recovery->recovery_amount;
        });

        static::created(function ($recovery) {
            // تحديث رصيد الدفعة المقدمة
            $advance = $recovery->advancePayment;
            $advance->recovered_amount += $recovery->recovery_amount;
            $advance->balance_amount = $advance->advance_amount - $advance->recovered_amount;
            
            if ($advance->balance_amount <= 0) {
                $advance->status = 'fully_recovered';
            } else {
                $advance->status = 'partially_recovered';
            }
            
            $advance->save();
        });
    }
}
