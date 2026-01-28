<?php

namespace App\Models\FinanceAccounting;

use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChequeReceived extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cheques_received';

    protected $fillable = [
        'company_id',
        'cheque_number',
        'bank_name',
        'branch_name',
        'drawer_account_number',
        'cheque_date',
        'due_date',
        'amount',
        'currency_id',
        'drawer_name',
        'drawer_type',
        'drawer_id',
        'memo',
        'reference_type',
        'reference_id',
        'status',
        'deposited_to_bank_id',
        'deposit_date',
        'collection_date',
        'return_date',
        'return_reason',
        'endorsed_to',
        'endorsement_date',
        'receipt_voucher_id',
        'journal_voucher_id',
        'created_by',
    ];

    protected $casts = [
        'cheque_date' => 'date',
        'due_date' => 'date',
        'deposit_date' => 'date',
        'collection_date' => 'date',
        'return_date' => 'date',
        'endorsement_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function depositedToBank(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'deposited_to_bank_id');
    }

    public function receiptVoucher(): BelongsTo
    {
        return $this->belongsTo(ReceiptVoucher::class);
    }

    public function journalVoucher(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sendForCollection(int $bankAccountId): void
    {
        $this->update([
            'status' => 'under_collection',
            'deposited_to_bank_id' => $bankAccountId,
            'deposit_date' => now(),
        ]);
    }

    public function markAsDeposited(int $bankAccountId, ?\DateTime $date = null): void
    {
        $this->update([
            'status' => 'deposited',
            'deposited_to_bank_id' => $bankAccountId,
            'deposit_date' => $date ?? now(),
        ]);
    }

    public function markAsCollected(?\DateTime $date = null): void
    {
        $this->update([
            'status' => 'collected',
            'collection_date' => $date ?? now(),
        ]);
    }

    public function markAsReturned(string $reason, ?\DateTime $date = null): void
    {
        $this->update([
            'status' => 'returned',
            'return_date' => $date ?? now(),
            'return_reason' => $reason,
        ]);
    }

    public function endorse(int $endorsedTo, ?\DateTime $date = null): void
    {
        $this->update([
            'status' => 'endorsed',
            'endorsed_to' => $endorsedTo,
            'endorsement_date' => $date ?? now(),
        ]);
    }

    public function getIsMaturedAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast();
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }
        return now()->diffInDays($this->due_date, false);
    }
}
