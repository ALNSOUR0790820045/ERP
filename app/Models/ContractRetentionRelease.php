<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractRetentionRelease extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_retention_id',
        'release_number',
        'release_date',
        'release_amount',
        'release_percentage',
        'reason',
        'conditions_met',
        'approval_date',
        'approved_by',
        'payment_voucher_id',
        'notes',
    ];

    protected $casts = [
        'release_date' => 'date',
        'release_amount' => 'decimal:3',
        'release_percentage' => 'decimal:2',
        'conditions_met' => 'array',
        'approval_date' => 'date',
    ];

    // العلاقات
    public function contractRetention(): BelongsTo
    {
        return $this->belongsTo(ContractRetention::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paymentVoucher(): BelongsTo
    {
        return $this->belongsTo(PaymentVoucher::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($release) {
            // تحديث رصيد المحتجز
            $retention = $release->contractRetention;
            $retention->released_amount += $release->release_amount;
            $retention->balance_amount = $retention->total_retained_amount - $retention->released_amount;
            
            if ($retention->balance_amount <= 0) {
                $retention->status = 'fully_released';
            } else {
                $retention->status = 'partially_released';
            }
            
            $retention->save();
        });
    }
}
