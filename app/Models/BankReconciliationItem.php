<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_reconciliation_id',
        'item_type',
        'reference_type',
        'reference_id',
        'reference_number',
        'date',
        'description',
        'amount',
        'is_reconciled',
        'reconciled_date',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:3',
        'is_reconciled' => 'boolean',
        'reconciled_date' => 'date',
    ];

    // العلاقات
    public function bankReconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    // الثوابت
    public const ITEM_TYPES = [
        'deposit_in_transit' => 'إيداع في الطريق',
        'outstanding_check' => 'شيك معلق',
        'bank_charge' => 'رسوم بنكية',
        'bank_interest' => 'فوائد بنكية',
        'error_correction' => 'تصحيح خطأ',
        'other' => 'أخرى',
    ];

    public const REFERENCE_TYPES = [
        'receipt_voucher' => 'سند قبض',
        'payment_voucher' => 'سند صرف',
        'journal_entry' => 'قيد يومية',
    ];

    public function getItemTypeLabelAttribute(): string
    {
        return self::ITEM_TYPES[$this->item_type] ?? $this->item_type;
    }
}
