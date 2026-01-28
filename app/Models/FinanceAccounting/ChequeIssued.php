<?php

namespace App\Models\FinanceAccounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChequeIssued extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cheques_issued';

    protected $fillable = [
        'cheque_book_id',
        'bank_account_id',
        'cheque_number',
        'cheque_date',
        'due_date',
        'amount',
        'currency_id',
        'payee_name',
        'payee_type',
        'payee_id',
        'amount_in_words',
        'memo',
        'reference_type',
        'reference_id',
        'status',
        'cleared_date',
        'bounced_date',
        'bounce_reason',
        'payment_voucher_id',
        'journal_voucher_id',
        'print_count',
        'last_printed_at',
        'printed_by',
        'created_by',
    ];

    protected $casts = [
        'cheque_date' => 'date',
        'due_date' => 'date',
        'cleared_date' => 'date',
        'bounced_date' => 'date',
        'amount' => 'decimal:2',
        'last_printed_at' => 'datetime',
    ];

    public function chequeBook(): BelongsTo
    {
        return $this->belongsTo(ChequeBook::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function paymentVoucher(): BelongsTo
    {
        return $this->belongsTo(PaymentVoucher::class);
    }

    public function journalVoucher(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    public function markAsIssued(): void
    {
        $this->update(['status' => 'issued']);
    }

    public function markAsCleared(?\DateTime $date = null): void
    {
        $this->update([
            'status' => 'cleared',
            'cleared_date' => $date ?? now(),
        ]);
    }

    public function markAsBounced(string $reason, ?\DateTime $date = null): void
    {
        $this->update([
            'status' => 'bounced',
            'bounced_date' => $date ?? now(),
            'bounce_reason' => $reason,
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function stopPayment(): void
    {
        $this->update(['status' => 'stopped']);
    }

    public function recordPrint(?int $userId = null): void
    {
        $this->update([
            'print_count' => $this->print_count + 1,
            'last_printed_at' => now(),
            'printed_by' => $userId ?? auth()->id(),
            'status' => $this->status === 'draft' ? 'printed' : $this->status,
        ]);
    }

    public function getIsOverdueAttribute(): bool
    {
        if (in_array($this->status, ['cleared', 'cancelled', 'stopped'])) {
            return false;
        }
        return $this->due_date && $this->due_date->isPast();
    }

    public static function convertToWords(float $amount): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $integerPart = (int) $amount;
        $decimalPart = round(($amount - $integerPart) * 100);

        $words = '';

        if ($integerPart >= 1000000) {
            $words .= self::convertToWords((int)($integerPart / 1000000)) . ' Million ';
            $integerPart %= 1000000;
        }

        if ($integerPart >= 1000) {
            $words .= self::convertToWords((int)($integerPart / 1000)) . ' Thousand ';
            $integerPart %= 1000;
        }

        if ($integerPart >= 100) {
            $words .= $ones[(int)($integerPart / 100)] . ' Hundred ';
            $integerPart %= 100;
        }

        if ($integerPart >= 20) {
            $words .= $tens[(int)($integerPart / 10)] . ' ';
            $integerPart %= 10;
        }

        if ($integerPart > 0) {
            $words .= $ones[$integerPart] . ' ';
        }

        $words = trim($words);

        if ($decimalPart > 0) {
            $words .= ' and ' . $decimalPart . '/100';
        }

        return $words . ' Only';
    }
}
