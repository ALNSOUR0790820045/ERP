<?php

namespace App\Services\FinanceAccounting;

use App\Models\FinanceAccounting\ChequeBook;
use App\Models\FinanceAccounting\ChequeIssued;
use App\Models\FinanceAccounting\ChequeReceived;
use App\Models\FinanceAccounting\ChequePrintTemplate;
use App\Models\FinanceAccounting\JournalVoucher;
use App\Models\FinanceAccounting\JournalVoucherLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Cheque Management Service
 * خدمة إدارة الشيكات
 */
class ChequeService
{
    /**
     * Issue a new cheque
     * إصدار شيك جديد
     */
    public function issueCheque(array $data): ChequeIssued
    {
        return DB::transaction(function () use ($data) {
            $chequeBook = ChequeBook::findOrFail($data['cheque_book_id']);
            
            // Get next cheque number if not provided
            $chequeNumber = $data['cheque_number'] ?? $chequeBook->getNextChequeNumber();
            
            if (!$chequeNumber) {
                throw new \Exception('No more cheques available in this book');
            }

            // Calculate amount in words
            $amountInWords = $data['amount_in_words'] ?? ChequeIssued::convertToWords($data['amount']);

            $cheque = ChequeIssued::create([
                'cheque_book_id' => $chequeBook->id,
                'bank_account_id' => $chequeBook->bank_account_id,
                'cheque_number' => $chequeNumber,
                'cheque_date' => $data['cheque_date'],
                'due_date' => $data['due_date'] ?? $data['cheque_date'],
                'amount' => $data['amount'],
                'currency_id' => $data['currency_id'] ?? null,
                'payee_name' => $data['payee_name'],
                'payee_type' => $data['payee_type'] ?? 'supplier',
                'payee_id' => $data['payee_id'] ?? null,
                'amount_in_words' => $amountInWords,
                'memo' => $data['memo'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'payment_voucher_id' => $data['payment_voucher_id'] ?? null,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            // Increment cheque book counter
            $chequeBook->incrementChequeNumber();

            return $cheque;
        });
    }

    /**
     * Print cheque
     * طباعة الشيك
     */
    public function printCheque(ChequeIssued $cheque, ?int $templateId = null): array
    {
        $template = $templateId 
            ? ChequePrintTemplate::findOrFail($templateId)
            : ChequePrintTemplate::where('bank_account_id', $cheque->bank_account_id)
                ->where('is_default', true)
                ->first();

        if (!$template) {
            $template = ChequePrintTemplate::where('is_default', true)->first();
        }

        $fieldPositions = $template?->field_positions ?? ChequePrintTemplate::getDefaultFieldPositions();

        // Record print
        $cheque->recordPrint(auth()->id());

        return [
            'cheque' => $cheque,
            'template' => $template,
            'print_data' => [
                'date' => $cheque->cheque_date->format('d/m/Y'),
                'payee' => $cheque->payee_name,
                'amount_numeric' => number_format($cheque->amount, 2),
                'amount_words' => $cheque->amount_in_words,
                'memo' => $cheque->memo,
            ],
            'field_positions' => $fieldPositions,
        ];
    }

    /**
     * Mark cheque as cleared
     * تأشير الشيك كمصروف
     */
    public function clearCheque(ChequeIssued $cheque, ?\DateTime $clearedDate = null): void
    {
        if (!in_array($cheque->status, ['issued', 'printed'])) {
            throw new \Exception('Only issued or printed cheques can be cleared');
        }

        $cheque->markAsCleared($clearedDate);
    }

    /**
     * Mark cheque as bounced
     * تأشير الشيك كمرتجع
     */
    public function bounceCheque(ChequeIssued $cheque, string $reason): void
    {
        if (!in_array($cheque->status, ['issued', 'printed'])) {
            throw new \Exception('Only issued or printed cheques can be bounced');
        }

        $cheque->markAsBounced($reason);

        // Create reversal journal entry if needed
        if ($cheque->journal_voucher_id) {
            $this->createBouncedChequeReversal($cheque);
        }
    }

    /**
     * Create bounced cheque reversal
     */
    private function createBouncedChequeReversal(ChequeIssued $cheque): JournalVoucher
    {
        $originalJournal = $cheque->journalVoucher;

        return DB::transaction(function () use ($cheque, $originalJournal) {
            $journal = JournalVoucher::create([
                'company_id' => $originalJournal->company_id,
                'voucher_number' => JournalVoucher::generateVoucherNumber(),
                'voucher_date' => now(),
                'reference_type' => 'cheque_bounced',
                'reference_id' => $cheque->id,
                'description' => "Reversal for bounced cheque: {$cheque->cheque_number}",
                'total_debit' => $originalJournal->total_credit,
                'total_credit' => $originalJournal->total_debit,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            // Reverse the original entries
            foreach ($originalJournal->lines as $line) {
                JournalVoucherLine::create([
                    'journal_voucher_id' => $journal->id,
                    'account_id' => $line->account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'description' => 'Reversal - ' . $line->description,
                ]);
            }

            return $journal;
        });
    }

    /**
     * Stop payment on cheque
     * إيقاف صرف الشيك
     */
    public function stopPayment(ChequeIssued $cheque): void
    {
        if (in_array($cheque->status, ['cleared', 'cancelled'])) {
            throw new \Exception('Cannot stop payment on cleared or cancelled cheques');
        }

        $cheque->stopPayment();
    }

    /**
     * Cancel cheque
     * إلغاء الشيك
     */
    public function cancelCheque(ChequeIssued $cheque): void
    {
        if ($cheque->status === 'cleared') {
            throw new \Exception('Cannot cancel cleared cheques');
        }

        DB::transaction(function () use ($cheque) {
            $cheque->markAsCancelled();

            // Update cheque book
            $chequeBook = $cheque->chequeBook;
            $chequeBook->cancelled_cheques++;
            $chequeBook->save();
        });
    }

    /**
     * Receive a cheque
     * استلام شيك
     */
    public function receiveCheque(array $data): ChequeReceived
    {
        return ChequeReceived::create([
            'company_id' => $data['company_id'] ?? null,
            'cheque_number' => $data['cheque_number'],
            'bank_name' => $data['bank_name'],
            'branch_name' => $data['branch_name'] ?? null,
            'drawer_account_number' => $data['drawer_account_number'] ?? null,
            'cheque_date' => $data['cheque_date'],
            'due_date' => $data['due_date'] ?? $data['cheque_date'],
            'amount' => $data['amount'],
            'currency_id' => $data['currency_id'] ?? null,
            'drawer_name' => $data['drawer_name'],
            'drawer_type' => $data['drawer_type'] ?? 'customer',
            'drawer_id' => $data['drawer_id'] ?? null,
            'memo' => $data['memo'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'receipt_voucher_id' => $data['receipt_voucher_id'] ?? null,
            'status' => 'received',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Deposit cheque to bank
     * إيداع الشيك في البنك
     */
    public function depositCheque(ChequeReceived $cheque, int $bankAccountId): void
    {
        if ($cheque->status !== 'received') {
            throw new \Exception('Only received cheques can be deposited');
        }

        $cheque->markAsDeposited($bankAccountId);
    }

    /**
     * Send cheque for collection
     * إرسال الشيك للتحصيل
     */
    public function sendForCollection(ChequeReceived $cheque, int $bankAccountId): void
    {
        if (!in_array($cheque->status, ['received', 'deposited'])) {
            throw new \Exception('Cheque cannot be sent for collection');
        }

        $cheque->sendForCollection($bankAccountId);
    }

    /**
     * Mark received cheque as collected
     * تأشير الشيك المستلم كمحصل
     */
    public function collectCheque(ChequeReceived $cheque): void
    {
        if (!in_array($cheque->status, ['under_collection', 'deposited'])) {
            throw new \Exception('Only deposited or under-collection cheques can be collected');
        }

        $cheque->markAsCollected();
    }

    /**
     * Return received cheque
     * إرجاع الشيك المستلم
     */
    public function returnCheque(ChequeReceived $cheque, string $reason): void
    {
        if (!in_array($cheque->status, ['deposited', 'under_collection'])) {
            throw new \Exception('Only deposited or under-collection cheques can be returned');
        }

        $cheque->markAsReturned($reason);
    }

    /**
     * Endorse cheque to another party
     * تظهير الشيك لطرف آخر
     */
    public function endorseCheque(ChequeReceived $cheque, int $endorsedToId): void
    {
        if ($cheque->status !== 'received') {
            throw new \Exception('Only received cheques can be endorsed');
        }

        $cheque->endorse($endorsedToId);
    }

    /**
     * Get cheques due for collection
     * الحصول على الشيكات المستحقة للتحصيل
     */
    public function getChequesForCollection(?Carbon $fromDate = null, ?Carbon $toDate = null): Collection
    {
        $query = ChequeReceived::whereIn('status', ['received', 'deposited', 'under_collection'])
            ->where('due_date', '<=', $toDate ?? now());

        if ($fromDate) {
            $query->where('due_date', '>=', $fromDate);
        }

        return $query->orderBy('due_date')->get();
    }

    /**
     * Get outstanding issued cheques
     * الحصول على الشيكات الصادرة المعلقة
     */
    public function getOutstandingIssuedCheques(?Carbon $asOfDate = null): Collection
    {
        return ChequeIssued::whereIn('status', ['issued', 'printed'])
            ->where('cheque_date', '<=', $asOfDate ?? now())
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Get cheque statistics
     * الحصول على إحصائيات الشيكات
     */
    public function getStatistics(): array
    {
        $issuedStats = ChequeIssued::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "issued" THEN 1 ELSE 0 END) as issued,
            SUM(CASE WHEN status = "cleared" THEN 1 ELSE 0 END) as cleared,
            SUM(CASE WHEN status = "bounced" THEN 1 ELSE 0 END) as bounced,
            SUM(CASE WHEN status IN ("issued", "printed") THEN amount ELSE 0 END) as outstanding_amount
        ')->first();

        $receivedStats = ChequeReceived::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "received" THEN 1 ELSE 0 END) as received,
            SUM(CASE WHEN status = "under_collection" THEN 1 ELSE 0 END) as under_collection,
            SUM(CASE WHEN status = "collected" THEN 1 ELSE 0 END) as collected,
            SUM(CASE WHEN status = "returned" THEN 1 ELSE 0 END) as returned,
            SUM(CASE WHEN status IN ("received", "under_collection") THEN amount ELSE 0 END) as pending_amount
        ')->first();

        return [
            'issued' => $issuedStats,
            'received' => $receivedStats,
        ];
    }
}
