<?php

namespace App\Models\FinanceAccounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LcAmendment extends Model
{
    use HasFactory;

    protected $fillable = [
        'letter_of_credit_id',
        'amendment_number',
        'amendment_date',
        'amendment_type',
        'description',
        'amount_change',
        'new_expiry_date',
        'new_shipment_date',
        'amendment_fees',
        'status',
        'created_by',
    ];

    protected $casts = [
        'amendment_date' => 'date',
        'new_expiry_date' => 'date',
        'new_shipment_date' => 'date',
        'amount_change' => 'decimal:2',
        'amendment_fees' => 'decimal:2',
    ];

    public function letterOfCredit(): BelongsTo
    {
        return $this->belongsTo(LetterOfCredit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function accept(): void
    {
        $this->update(['status' => 'accepted']);

        $lc = $this->letterOfCredit;

        // Apply amendment changes
        if ($this->amount_change) {
            $lc->lc_amount += $this->amount_change;
        }
        if ($this->new_expiry_date) {
            $lc->expiry_date = $this->new_expiry_date;
        }
        if ($this->new_shipment_date) {
            $lc->latest_shipment_date = $this->new_shipment_date;
        }

        $lc->status = 'amended';
        $lc->updateAvailableAmount();
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }
}
