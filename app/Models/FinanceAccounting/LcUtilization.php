<?php

namespace App\Models\FinanceAccounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LcUtilization extends Model
{
    use HasFactory;

    protected $fillable = [
        'letter_of_credit_id',
        'utilization_number',
        'utilization_date',
        'amount',
        'shipment_reference',
        'shipment_date',
        'documents_presented',
        'status',
        'discrepancies',
        'supplier_invoice_id',
        'journal_voucher_id',
    ];

    protected $casts = [
        'utilization_date' => 'date',
        'shipment_date' => 'date',
        'amount' => 'decimal:2',
        'documents_presented' => 'array',
    ];

    public function letterOfCredit(): BelongsTo
    {
        return $this->belongsTo(LetterOfCredit::class);
    }

    public function supplierInvoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class);
    }

    public function journalVoucher(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class);
    }

    public function accept(): void
    {
        $this->update(['status' => 'accepted']);
        $this->letterOfCredit->updateAvailableAmount();
    }

    public function markAsDiscrepant(string $discrepancies): void
    {
        $this->update([
            'status' => 'discrepant',
            'discrepancies' => $discrepancies,
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => 'paid']);
        $this->letterOfCredit->updateAvailableAmount();
    }
}
