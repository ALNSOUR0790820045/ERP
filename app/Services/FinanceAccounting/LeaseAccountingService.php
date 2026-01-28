<?php

namespace App\Services\FinanceAccounting;

use App\Models\FinanceAccounting\Lease;
use App\Models\FinanceAccounting\LeasePaymentSchedule;
use App\Models\FinanceAccounting\LeaseDepreciation;
use App\Models\FinanceAccounting\LeaseModification;
use App\Models\FinanceAccounting\JournalVoucher;
use App\Models\FinanceAccounting\JournalVoucherLine;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Lease Accounting Service - IFRS 16 Implementation
 * خدمة محاسبة عقود الإيجار - تطبيق المعيار الدولي للتقارير المالية 16
 */
class LeaseAccountingService
{
    /**
     * Calculate initial recognition amounts
     * حساب مبالغ الاعتراف المبدئي
     */
    public function calculateInitialRecognition(Lease $lease): array
    {
        $payments = $this->getLeasePayments($lease);
        $rate = $this->getPeriodicRate($lease);
        
        // Calculate present value of lease payments
        $presentValue = 0;
        foreach ($payments as $index => $payment) {
            $period = $index + 1;
            if ($lease->payment_timing === 'beginning') {
                $period = $index;
            }
            $discountFactor = pow(1 + $rate, -$period);
            $presentValue += $payment['amount'] * $discountFactor;
        }

        $leaseLiability = round($presentValue, 2);
        $rouAsset = $leaseLiability + $lease->initial_direct_costs - $lease->lease_incentives + $lease->restoration_costs;

        return [
            'lease_liability' => $leaseLiability,
            'right_of_use_asset' => round($rouAsset, 2),
            'number_of_payments' => count($payments),
            'total_payments' => array_sum(array_column($payments, 'amount')),
            'total_interest' => array_sum(array_column($payments, 'amount')) - $leaseLiability,
        ];
    }

    /**
     * Generate payment schedule
     * إنشاء جدول السداد
     */
    public function generatePaymentSchedule(Lease $lease): array
    {
        $schedule = [];
        $rate = $this->getPeriodicRate($lease);
        $initialRecognition = $this->calculateInitialRecognition($lease);
        $openingBalance = $initialRecognition['lease_liability'];
        
        $payments = $this->getLeasePayments($lease);
        $paymentDate = Carbon::parse($lease->commencement_date);
        
        if ($lease->payment_timing === 'end') {
            $paymentDate = $this->addPaymentPeriod($paymentDate, $lease->payment_frequency);
        }

        foreach ($payments as $index => $payment) {
            // Calculate interest for this period
            $interest = round($openingBalance * $rate, 2);
            $principal = $payment['amount'] - $interest;
            $closingBalance = $openingBalance - $principal;

            $schedule[] = [
                'period' => $index + 1,
                'payment_date' => $paymentDate->format('Y-m-d'),
                'payment_amount' => $payment['amount'],
                'interest_amount' => $interest,
                'principal_amount' => $principal,
                'opening_balance' => round($openingBalance, 2),
                'closing_balance' => max(0, round($closingBalance, 2)),
            ];

            $openingBalance = $closingBalance;
            $paymentDate = $this->addPaymentPeriod($paymentDate, $lease->payment_frequency);
        }

        return $schedule;
    }

    /**
     * Create payment schedule records in database
     * إنشاء سجلات جدول السداد في قاعدة البيانات
     */
    public function createPaymentScheduleRecords(Lease $lease): void
    {
        $schedule = $this->generatePaymentSchedule($lease);

        foreach ($schedule as $item) {
            LeasePaymentSchedule::create([
                'lease_id' => $lease->id,
                'payment_number' => $item['period'],
                'due_date' => $item['payment_date'],
                'payment_amount' => $item['payment_amount'],
                'principal_amount' => $item['principal_amount'],
                'interest_amount' => $item['interest_amount'],
                'opening_balance' => $item['opening_balance'],
                'closing_balance' => $item['closing_balance'],
                'status' => 'scheduled',
            ]);
        }
    }

    /**
     * Record initial recognition journal entry
     * تسجيل قيد الاعتراف المبدئي
     */
    public function recordInitialRecognition(Lease $lease): ?JournalVoucher
    {
        if (!$lease->rou_asset_account_id || !$lease->lease_liability_account_id) {
            return null;
        }

        $recognition = $this->calculateInitialRecognition($lease);

        return DB::transaction(function () use ($lease, $recognition) {
            $journal = JournalVoucher::create([
                'company_id' => $lease->company_id,
                'voucher_number' => JournalVoucher::generateVoucherNumber(),
                'voucher_date' => $lease->commencement_date,
                'reference_type' => 'lease',
                'reference_id' => $lease->id,
                'description' => "Initial recognition for lease: {$lease->lease_name}",
                'total_debit' => $recognition['right_of_use_asset'],
                'total_credit' => $recognition['right_of_use_asset'],
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            // Debit: Right-of-Use Asset
            JournalVoucherLine::create([
                'journal_voucher_id' => $journal->id,
                'account_id' => $lease->rou_asset_account_id,
                'debit' => $recognition['right_of_use_asset'],
                'credit' => 0,
                'description' => 'Right-of-Use Asset',
            ]);

            // Credit: Lease Liability
            JournalVoucherLine::create([
                'journal_voucher_id' => $journal->id,
                'account_id' => $lease->lease_liability_account_id,
                'debit' => 0,
                'credit' => $recognition['lease_liability'],
                'description' => 'Lease Liability',
            ]);

            // Handle initial direct costs and restoration costs as needed
            $otherCredits = $recognition['right_of_use_asset'] - $recognition['lease_liability'];
            if ($otherCredits > 0) {
                // This would need appropriate accounts for cash/payables
            }

            // Update lease with calculated amounts
            $lease->update([
                'right_of_use_asset' => $recognition['right_of_use_asset'],
                'lease_liability' => $recognition['lease_liability'],
                'status' => 'active',
            ]);

            return $journal;
        });
    }

    /**
     * Calculate monthly depreciation
     * حساب الإهلاك الشهري
     */
    public function calculateDepreciation(Lease $lease): float
    {
        $depreciableAmount = $lease->right_of_use_asset - $lease->restoration_costs;
        return round($depreciableAmount / $lease->lease_term_months, 2);
    }

    /**
     * Record depreciation for a period
     * تسجيل الإهلاك لفترة
     */
    public function recordDepreciation(Lease $lease, $fiscalPeriodId = null): ?LeaseDepreciation
    {
        if (!$lease->depreciation_expense_account_id) {
            return null;
        }

        $depreciationAmount = $this->calculateDepreciation($lease);
        $newAccumulatedDepreciation = $lease->accumulated_depreciation + $depreciationAmount;
        $netBookValue = $lease->right_of_use_asset - $newAccumulatedDepreciation;

        return DB::transaction(function () use ($lease, $depreciationAmount, $newAccumulatedDepreciation, $netBookValue, $fiscalPeriodId) {
            // Create journal entry
            $journal = JournalVoucher::create([
                'company_id' => $lease->company_id,
                'voucher_number' => JournalVoucher::generateVoucherNumber(),
                'voucher_date' => now(),
                'reference_type' => 'lease_depreciation',
                'reference_id' => $lease->id,
                'description' => "ROU Asset Depreciation - {$lease->lease_name}",
                'total_debit' => $depreciationAmount,
                'total_credit' => $depreciationAmount,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            // Debit: Depreciation Expense
            JournalVoucherLine::create([
                'journal_voucher_id' => $journal->id,
                'account_id' => $lease->depreciation_expense_account_id,
                'debit' => $depreciationAmount,
                'credit' => 0,
                'description' => 'Depreciation Expense',
            ]);

            // Credit: Accumulated Depreciation (ROU Asset)
            JournalVoucherLine::create([
                'journal_voucher_id' => $journal->id,
                'account_id' => $lease->rou_asset_account_id,
                'debit' => 0,
                'credit' => $depreciationAmount,
                'description' => 'Accumulated Depreciation',
            ]);

            // Create depreciation record
            $depreciation = LeaseDepreciation::create([
                'lease_id' => $lease->id,
                'fiscal_period_id' => $fiscalPeriodId,
                'depreciation_date' => now(),
                'depreciation_amount' => $depreciationAmount,
                'accumulated_depreciation' => $newAccumulatedDepreciation,
                'net_book_value' => $netBookValue,
                'journal_voucher_id' => $journal->id,
                'status' => 'posted',
            ]);

            // Update lease
            $lease->update([
                'accumulated_depreciation' => $newAccumulatedDepreciation,
            ]);

            return $depreciation;
        });
    }

    /**
     * Process lease payment
     * معالجة دفعة الإيجار
     */
    public function processPayment(LeasePaymentSchedule $schedule): ?JournalVoucher
    {
        $lease = $schedule->lease;

        if (!$lease->lease_liability_account_id || !$lease->interest_expense_account_id) {
            return null;
        }

        return DB::transaction(function () use ($schedule, $lease) {
            // Create journal entry
            $journal = JournalVoucher::create([
                'company_id' => $lease->company_id,
                'voucher_number' => JournalVoucher::generateVoucherNumber(),
                'voucher_date' => now(),
                'reference_type' => 'lease_payment',
                'reference_id' => $schedule->id,
                'description' => "Lease Payment #{$schedule->payment_number} - {$lease->lease_name}",
                'total_debit' => $schedule->payment_amount,
                'total_credit' => $schedule->payment_amount,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            // Debit: Lease Liability (Principal)
            JournalVoucherLine::create([
                'journal_voucher_id' => $journal->id,
                'account_id' => $lease->lease_liability_account_id,
                'debit' => $schedule->principal_amount,
                'credit' => 0,
                'description' => 'Principal Payment',
            ]);

            // Debit: Interest Expense
            JournalVoucherLine::create([
                'journal_voucher_id' => $journal->id,
                'account_id' => $lease->interest_expense_account_id,
                'debit' => $schedule->interest_amount,
                'credit' => 0,
                'description' => 'Interest Expense',
            ]);

            // Credit: Cash/Bank would go here
            // This would need the appropriate cash account

            // Update payment schedule
            $schedule->update([
                'status' => 'paid',
                'paid_date' => now(),
                'journal_voucher_id' => $journal->id,
            ]);

            return $journal;
        });
    }

    /**
     * Handle lease modification
     * معالجة تعديل عقد الإيجار
     */
    public function handleModification(Lease $lease, array $modificationData): LeaseModification
    {
        return DB::transaction(function () use ($lease, $modificationData) {
            // Calculate revised lease liability
            $revisedPayments = [];
            $remainingTerm = $modificationData['new_term_months'] ?? 
                ($lease->end_date->diffInMonths(now()));
            
            for ($i = 0; $i < $remainingTerm; $i++) {
                $revisedPayments[] = ['amount' => $modificationData['new_payment'] ?? $lease->monthly_payment];
            }

            $rate = $this->getPeriodicRate($lease);
            $revisedLiability = 0;
            foreach ($revisedPayments as $index => $payment) {
                $discountFactor = pow(1 + $rate, -($index + 1));
                $revisedLiability += $payment['amount'] * $discountFactor;
            }

            // Calculate ROU asset adjustment
            $currentLiability = $lease->lease_liability - 
                $lease->paymentSchedules()->where('status', 'paid')->sum('principal_amount');
            
            $liabilityChange = $revisedLiability - $currentLiability;
            $gainLoss = 0;

            // For scope decrease, recognize gain/loss
            if ($modificationData['modification_type'] === 'scope_decrease') {
                $proportionateDecrease = $modificationData['scope_decrease_percentage'] / 100;
                $rouAdjustment = -($lease->right_of_use_asset - $lease->accumulated_depreciation) * $proportionateDecrease;
                $gainLoss = $rouAdjustment - ($currentLiability * $proportionateDecrease);
            } else {
                $rouAdjustment = $liabilityChange;
            }

            // Create modification record
            $modification = LeaseModification::create([
                'lease_id' => $lease->id,
                'modification_date' => now(),
                'modification_type' => $modificationData['modification_type'],
                'description' => $modificationData['description'],
                'revised_lease_liability' => round($revisedLiability, 2),
                'rou_asset_adjustment' => round($rouAdjustment, 2),
                'gain_loss' => round($gainLoss, 2),
                'created_by' => auth()->id(),
            ]);

            // Update lease
            $lease->update([
                'lease_liability' => round($revisedLiability, 2),
                'right_of_use_asset' => $lease->right_of_use_asset + $rouAdjustment,
                'monthly_payment' => $modificationData['new_payment'] ?? $lease->monthly_payment,
                'end_date' => $modificationData['new_end_date'] ?? $lease->end_date,
                'status' => 'modified',
            ]);

            return $modification;
        });
    }

    /**
     * Get periodic discount rate
     */
    private function getPeriodicRate(Lease $lease): float
    {
        $annualRate = $lease->incremental_borrowing_rate / 100;
        
        return match($lease->payment_frequency) {
            'monthly' => $annualRate / 12,
            'quarterly' => $annualRate / 4,
            'semi_annual' => $annualRate / 2,
            'annual' => $annualRate,
        };
    }

    /**
     * Get lease payments array
     */
    private function getLeasePayments(Lease $lease): array
    {
        $frequency = match($lease->payment_frequency) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annual' => 6,
            'annual' => 12,
        };

        $numberOfPayments = ceil($lease->lease_term_months / $frequency);
        $payments = [];

        for ($i = 0; $i < $numberOfPayments; $i++) {
            $payments[] = ['amount' => $lease->monthly_payment];
        }

        return $payments;
    }

    /**
     * Add payment period to date
     */
    private function addPaymentPeriod(Carbon $date, string $frequency): Carbon
    {
        return match($frequency) {
            'monthly' => $date->addMonth(),
            'quarterly' => $date->addMonths(3),
            'semi_annual' => $date->addMonths(6),
            'annual' => $date->addYear(),
        };
    }
}
