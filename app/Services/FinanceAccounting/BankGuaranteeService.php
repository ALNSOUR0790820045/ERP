<?php

namespace App\Services\FinanceAccounting;

use App\Models\FinanceAccounting\BankGuarantee;
use App\Models\FinanceAccounting\GuaranteeRenewal;
use App\Models\FinanceAccounting\LetterOfCredit;
use App\Models\FinanceAccounting\LcAmendment;
use App\Models\FinanceAccounting\LcUtilization;
use App\Models\FinanceAccounting\JournalVoucher;
use App\Models\FinanceAccounting\JournalVoucherLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Bank Guarantee & Letter of Credit Service
 * خدمة الضمانات البنكية وخطابات الاعتماد
 */
class BankGuaranteeService
{
    // =========================================
    // Bank Guarantee Methods
    // =========================================

    /**
     * Create a new bank guarantee
     * إنشاء ضمان بنكي جديد
     */
    public function createGuarantee(array $data): BankGuarantee
    {
        return BankGuarantee::create([
            'company_id' => $data['company_id'] ?? null,
            'guarantee_number' => $data['guarantee_number'] ?? BankGuarantee::generateGuaranteeNumber(),
            'guarantee_type' => $data['guarantee_type'],
            'name' => $data['name'],
            'bank_id' => $data['bank_id'],
            'bank_name' => $data['bank_name'],
            'beneficiary_name' => $data['beneficiary_name'],
            'beneficiary_id' => $data['beneficiary_id'] ?? null,
            'project_id' => $data['project_id'] ?? null,
            'contract_id' => $data['contract_id'] ?? null,
            'issue_date' => $data['issue_date'],
            'expiry_date' => $data['expiry_date'],
            'amount' => $data['amount'],
            'currency_id' => $data['currency_id'] ?? null,
            'margin_amount' => $data['margin_amount'] ?? 0,
            'commission_rate' => $data['commission_rate'] ?? 0,
            'commission_amount' => $data['commission_amount'] ?? 0,
            'purpose' => $data['purpose'] ?? null,
            'terms' => $data['terms'] ?? null,
            'status' => 'active',
            'document_path' => $data['document_path'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Renew a bank guarantee
     * تجديد الضمان البنكي
     */
    public function renewGuarantee(BankGuarantee $guarantee, array $data): GuaranteeRenewal
    {
        return DB::transaction(function () use ($guarantee, $data) {
            $renewal = GuaranteeRenewal::create([
                'bank_guarantee_id' => $guarantee->id,
                'renewal_number' => $this->generateRenewalNumber($guarantee),
                'renewal_date' => $data['renewal_date'] ?? now(),
                'old_expiry_date' => $guarantee->expiry_date,
                'new_expiry_date' => $data['new_expiry_date'],
                'old_amount' => $guarantee->amount,
                'new_amount' => $data['new_amount'] ?? $guarantee->amount,
                'renewal_fees' => $data['renewal_fees'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            return $renewal;
        });
    }

    /**
     * Approve guarantee renewal
     * الموافقة على تجديد الضمان
     */
    public function approveRenewal(GuaranteeRenewal $renewal): void
    {
        $renewal->approve(auth()->id());
    }

    /**
     * Release bank guarantee
     * تحرير الضمان البنكي
     */
    public function releaseGuarantee(BankGuarantee $guarantee, ?\DateTime $releaseDate = null): void
    {
        $guarantee->update([
            'status' => 'released',
            'released_date' => $releaseDate ?? now(),
        ]);
    }

    /**
     * Claim against bank guarantee
     * المطالبة بالضمان البنكي
     */
    public function claimGuarantee(BankGuarantee $guarantee, float $claimAmount, string $reason): void
    {
        if ($claimAmount > $guarantee->amount) {
            throw new \Exception('Claim amount cannot exceed guarantee amount');
        }

        $guarantee->update([
            'status' => 'claimed',
            'claimed_amount' => $claimAmount,
            'claim_reason' => $reason,
            'claimed_date' => now(),
        ]);
    }

    /**
     * Get expiring guarantees
     * الحصول على الضمانات التي توشك على الانتهاء
     */
    public function getExpiringGuarantees(int $daysAhead = 30): Collection
    {
        return BankGuarantee::where('status', 'active')
            ->where('expiry_date', '<=', now()->addDays($daysAhead))
            ->where('expiry_date', '>=', now())
            ->orderBy('expiry_date')
            ->get();
    }

    /**
     * Get guarantee statistics
     * إحصائيات الضمانات
     */
    public function getGuaranteeStatistics(): array
    {
        return BankGuarantee::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = "released" THEN 1 ELSE 0 END) as released,
            SUM(CASE WHEN status = "claimed" THEN 1 ELSE 0 END) as claimed,
            SUM(CASE WHEN status = "expired" THEN 1 ELSE 0 END) as expired,
            SUM(CASE WHEN status = "active" THEN amount ELSE 0 END) as total_active_amount,
            SUM(margin_amount) as total_margin_blocked
        ')->first()->toArray();
    }

    // =========================================
    // Letter of Credit Methods
    // =========================================

    /**
     * Create a new letter of credit
     * إنشاء خطاب اعتماد جديد
     */
    public function createLetterOfCredit(array $data): LetterOfCredit
    {
        $lcNumber = $data['lc_number'] ?? LetterOfCredit::generateLcNumber();

        return LetterOfCredit::create([
            'company_id' => $data['company_id'] ?? null,
            'supplier_id' => $data['supplier_id'],
            'purchase_order_id' => $data['purchase_order_id'] ?? null,
            'lc_number' => $lcNumber,
            'lc_type' => $data['lc_type'],
            'lc_name' => $data['lc_name'],
            'issuing_bank_id' => $data['issuing_bank_id'],
            'issuing_bank_name' => $data['issuing_bank_name'],
            'advising_bank' => $data['advising_bank'] ?? null,
            'confirming_bank' => $data['confirming_bank'] ?? null,
            'beneficiary_name' => $data['beneficiary_name'],
            'beneficiary_bank' => $data['beneficiary_bank'] ?? null,
            'issue_date' => $data['issue_date'],
            'expiry_date' => $data['expiry_date'],
            'latest_shipment_date' => $data['latest_shipment_date'] ?? null,
            'lc_amount' => $data['lc_amount'],
            'currency_id' => $data['currency_id'] ?? null,
            'tolerance_percentage' => $data['tolerance_percentage'] ?? 0,
            'is_confirmed' => $data['is_confirmed'] ?? false,
            'is_transferable' => $data['is_transferable'] ?? false,
            'partial_shipment_allowed' => $data['partial_shipment_allowed'] ?? true,
            'transhipment_allowed' => $data['transhipment_allowed'] ?? true,
            'goods_description' => $data['goods_description'] ?? null,
            'port_of_loading' => $data['port_of_loading'] ?? null,
            'port_of_discharge' => $data['port_of_discharge'] ?? null,
            'incoterms' => $data['incoterms'] ?? null,
            'required_documents' => $data['required_documents'] ?? null,
            'margin_amount' => $data['margin_amount'] ?? 0,
            'commission_amount' => $data['commission_amount'] ?? 0,
            'available_amount' => $data['lc_amount'],
            'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
            'status' => 'issued',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Create LC amendment
     * إنشاء تعديل خطاب الاعتماد
     */
    public function createAmendment(LetterOfCredit $lc, array $data): LcAmendment
    {
        $amendmentNumber = $this->generateAmendmentNumber($lc);

        return LcAmendment::create([
            'letter_of_credit_id' => $lc->id,
            'amendment_number' => $amendmentNumber,
            'amendment_date' => $data['amendment_date'] ?? now(),
            'amendment_type' => $data['amendment_type'],
            'description' => $data['description'],
            'amount_change' => $data['amount_change'] ?? null,
            'new_expiry_date' => $data['new_expiry_date'] ?? null,
            'new_shipment_date' => $data['new_shipment_date'] ?? null,
            'amendment_fees' => $data['amendment_fees'] ?? 0,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Accept LC amendment
     * قبول تعديل خطاب الاعتماد
     */
    public function acceptAmendment(LcAmendment $amendment): void
    {
        $amendment->accept();
    }

    /**
     * Record LC utilization
     * تسجيل استخدام خطاب الاعتماد
     */
    public function recordUtilization(LetterOfCredit $lc, array $data): LcUtilization
    {
        $utilizationNumber = $this->generateUtilizationNumber($lc);

        $utilization = LcUtilization::create([
            'letter_of_credit_id' => $lc->id,
            'utilization_number' => $utilizationNumber,
            'utilization_date' => $data['utilization_date'] ?? now(),
            'amount' => $data['amount'],
            'shipment_reference' => $data['shipment_reference'] ?? null,
            'shipment_date' => $data['shipment_date'] ?? null,
            'documents_presented' => $data['documents_presented'] ?? null,
            'supplier_invoice_id' => $data['supplier_invoice_id'] ?? null,
            'status' => 'pending',
        ]);

        return $utilization;
    }

    /**
     * Accept LC utilization
     * قبول استخدام خطاب الاعتماد
     */
    public function acceptUtilization(LcUtilization $utilization): void
    {
        if ($utilization->amount > $utilization->letterOfCredit->available_amount) {
            throw new \Exception('Utilization amount exceeds available LC amount');
        }

        $utilization->accept();
    }

    /**
     * Mark utilization as discrepant
     * تأشير الاستخدام كغير مطابق
     */
    public function markUtilizationDiscrepant(LcUtilization $utilization, string $discrepancies): void
    {
        $utilization->markAsDiscrepant($discrepancies);
    }

    /**
     * Mark LC utilization as paid
     * تأشير استخدام خطاب الاعتماد كمدفوع
     */
    public function payUtilization(LcUtilization $utilization): void
    {
        $utilization->markAsPaid();
    }

    /**
     * Close letter of credit
     * إغلاق خطاب الاعتماد
     */
    public function closeLetterOfCredit(LetterOfCredit $lc): void
    {
        $lc->update(['status' => 'closed']);
    }

    /**
     * Get expiring LCs
     * الحصول على خطابات الاعتماد التي توشك على الانتهاء
     */
    public function getExpiringLCs(int $daysAhead = 30): Collection
    {
        return LetterOfCredit::whereIn('status', ['issued', 'amended'])
            ->where('expiry_date', '<=', now()->addDays($daysAhead))
            ->where('expiry_date', '>=', now())
            ->orderBy('expiry_date')
            ->get();
    }

    /**
     * Get LC statistics
     * إحصائيات خطابات الاعتماد
     */
    public function getLCStatistics(): array
    {
        return LetterOfCredit::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status IN ("issued", "amended") THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = "utilized" THEN 1 ELSE 0 END) as fully_utilized,
            SUM(CASE WHEN status = "closed" THEN 1 ELSE 0 END) as closed,
            SUM(lc_amount) as total_lc_amount,
            SUM(utilized_amount) as total_utilized,
            SUM(available_amount) as total_available,
            SUM(margin_amount) as total_margin_blocked
        ')->first()->toArray();
    }

    // =========================================
    // Helper Methods
    // =========================================

    private function generateRenewalNumber(BankGuarantee $guarantee): string
    {
        $count = GuaranteeRenewal::where('bank_guarantee_id', $guarantee->id)->count();
        return $guarantee->guarantee_number . '-R' . str_pad($count + 1, 2, '0', STR_PAD_LEFT);
    }

    private function generateAmendmentNumber(LetterOfCredit $lc): string
    {
        $count = LcAmendment::where('letter_of_credit_id', $lc->id)->count();
        return $lc->lc_number . '-A' . str_pad($count + 1, 2, '0', STR_PAD_LEFT);
    }

    private function generateUtilizationNumber(LetterOfCredit $lc): string
    {
        $count = LcUtilization::where('letter_of_credit_id', $lc->id)->count();
        return $lc->lc_number . '-U' . str_pad($count + 1, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Check for alerts (expiring guarantees and LCs)
     * فحص التنبيهات
     */
    public function getAlerts(int $daysAhead = 30): array
    {
        return [
            'expiring_guarantees' => $this->getExpiringGuarantees($daysAhead),
            'expiring_lcs' => $this->getExpiringLCs($daysAhead),
        ];
    }
}
