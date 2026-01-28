<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderPurchase extends Model
{
    protected $fillable = [
        'tender_id',
        'purchase_date',
        'receipt_number',
        'amount',
        'payment_method',
        'paid_by',
        'receipt_image',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPaymentMethodLabel(): string
    {
        return match($this->payment_method) {
            'cash' => 'نقداً',
            'check' => 'شيك',
            'transfer' => 'تحويل بنكي',
            default => $this->payment_method,
        };
    }
}
