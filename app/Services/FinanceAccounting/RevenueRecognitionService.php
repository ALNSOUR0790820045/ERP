<?php

namespace App\Services\FinanceAccounting;

use App\Models\FinanceAccounting\RevenueContract;
use App\Models\FinanceAccounting\PerformanceObligation;
use App\Models\FinanceAccounting\RevenueRecognitionSchedule;
use App\Models\FinanceAccounting\VariableConsideration;
use App\Models\FinanceAccounting\JournalVoucher;
use App\Models\FinanceAccounting\JournalVoucherLine;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Revenue Recognition Service - ASC 606/IFRS 15 Implementation
 * خدمة الاعتراف بالإيرادات - تطبيق معيار ASC 606/IFRS 15
 */
class RevenueRecognitionService
{
    /**
     * Create revenue contract with performance obligations
     * إنشاء عقد إيرادات مع التزامات الأداء
     */
    public function createContract(array $contractData, array $obligationsData): RevenueContract
    {
        return DB::transaction(function () use ($contractData, $obligationsData) {
            // Create the contract
            $contract = RevenueContract::create([
                'company_id' => $contractData['company_id'] ?? null,
                'contract_number' => $contractData['contract_number'] ?? $this->generateContractNumber(),
                'customer_id' => $contractData['customer_id'],
                'contract_date' => $contractData['contract_date'],
                'start_date' => $contractData['start_date'],
                'end_date' => $contractData['end_date'],
                'total_transaction_price' => $contractData['total_transaction_price'],
                'currency_id' => $contractData['currency_id'] ?? null,
                'payment_terms' => $contractData['payment_terms'] ?? null,
                'description' => $contractData['description'] ?? null,
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            // Calculate standalone selling prices total
            $totalSSP = array_sum(array_column($obligationsData, 'standalone_selling_price'));

            // Create performance obligations and allocate transaction price
            foreach ($obligationsData as $obligation) {
                $allocationPercentage = $obligation['standalone_selling_price'] / $totalSSP;
                $allocatedAmount = $contract->total_transaction_price * $allocationPercentage;

                PerformanceObligation::create([
                    'revenue_contract_id' => $contract->id,
                    'name' => $obligation['name'],
                    'description' => $obligation['description'] ?? null,
                    'satisfaction_pattern' => $obligation['satisfaction_pattern'],
                    'standalone_selling_price' => $obligation['standalone_selling_price'],
                    'allocated_transaction_price' => round($allocatedAmount, 2),
                    'progress_measure' => $obligation['progress_measure'] ?? null,
                    'expected_completion_date' => $obligation['expected_completion_date'] ?? null,
                    'status' => 'pending',
                ]);
            }

            return $contract->load('performanceObligations');
        });
    }

    /**
     * Allocate transaction price based on standalone selling prices
     * توزيع سعر المعاملة بناءً على أسعار البيع المستقلة
     */
    public function allocateTransactionPrice(RevenueContract $contract): void
    {
        $obligations = $contract->performanceObligations;
        $totalSSP = $obligations->sum('standalone_selling_price');
        
        // Adjust for variable consideration
        $variableConsiderations = $contract->variableConsiderations;
        $constrainedAmount = $variableConsiderations->sum('constraint_amount');
        $effectivePrice = $contract->total_transaction_price - $constrainedAmount;

        foreach ($obligations as $obligation) {
            $allocationPercentage = $obligation->standalone_selling_price / $totalSSP;
            $allocatedAmount = $effectivePrice * $allocationPercentage;

            $obligation->update([
                'allocated_transaction_price' => round($allocatedAmount, 2),
            ]);
        }
    }

    /**
     * Add variable consideration
     * إضافة مقابل متغير
     */
    public function addVariableConsideration(RevenueContract $contract, array $data): VariableConsideration
    {
        $consideration = VariableConsideration::create([
            'revenue_contract_id' => $contract->id,
            'consideration_type' => $data['consideration_type'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'estimation_method' => $data['estimation_method'],
            'estimated_amount' => $data['estimated_amount'],
            'constraint_amount' => $data['constraint_amount'] ?? null,
            'status' => 'estimated',
        ]);

        // Reallocate transaction price
        $this->allocateTransactionPrice($contract);

        return $consideration;
    }

    /**
     * Measure progress for over-time obligations
     * قياس التقدم للالتزامات على مدى الوقت
     */
    public function measureProgress(PerformanceObligation $obligation, float $progress): float
    {
        if ($obligation->satisfaction_pattern !== 'over_time') {
            throw new \Exception('Progress measurement only applies to over-time obligations');
        }

        $recognizableAmount = $obligation->allocated_transaction_price * ($progress / 100);
        $previouslyRecognized = $obligation->schedules()
            ->where('status', 'recognized')
            ->sum('amount');

        return max(0, $recognizableAmount - $previouslyRecognized);
    }

    /**
     * Recognize revenue for a performance obligation
     * الاعتراف بالإيرادات لالتزام أداء
     */
    public function recognizeRevenue(PerformanceObligation $obligation, float $amount, ?int $fiscalPeriodId = null): ?RevenueRecognitionSchedule
    {
        $contract = $obligation->revenueContract;
        
        // Check if recognition accounts are configured
        if (!$contract->revenue_account_id) {
            throw new \Exception('Revenue account not configured on contract');
        }

        $cumulativeRecognized = $obligation->schedules()
            ->where('status', 'recognized')
            ->sum('amount');

        return DB::transaction(function () use ($obligation, $amount, $cumulativeRecognized, $contract, $fiscalPeriodId) {
            // Create journal entry
            $journal = JournalVoucher::create([
                'company_id' => $contract->company_id,
                'voucher_number' => JournalVoucher::generateVoucherNumber(),
                'voucher_date' => now(),
                'fiscal_period_id' => $fiscalPeriodId,
                'reference_type' => 'revenue_recognition',
                'reference_id' => $obligation->id,
                'description' => "Revenue recognition - {$obligation->name}",
                'total_debit' => $amount,
                'total_credit' => $amount,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            // Debit: Accounts Receivable or Deferred Revenue
            JournalVoucherLine::create([
                'journal_voucher_id' => $journal->id,
                'account_id' => $contract->receivable_account_id ?? $contract->revenue_account_id,
                'debit' => $amount,
                'credit' => 0,
                'description' => 'Accounts Receivable',
            ]);

            // Credit: Revenue
            JournalVoucherLine::create([
                'journal_voucher_id' => $journal->id,
                'account_id' => $contract->revenue_account_id,
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Revenue',
            ]);

            // Create recognition schedule record
            $schedule = RevenueRecognitionSchedule::create([
                'performance_obligation_id' => $obligation->id,
                'fiscal_period_id' => $fiscalPeriodId,
                'recognition_date' => now(),
                'amount' => $amount,
                'cumulative_recognized' => $cumulativeRecognized + $amount,
                'journal_voucher_id' => $journal->id,
                'status' => 'recognized',
                'created_by' => auth()->id(),
            ]);

            // Check if obligation is fully satisfied
            $totalRecognized = $cumulativeRecognized + $amount;
            if ($totalRecognized >= $obligation->allocated_transaction_price) {
                $obligation->update(['status' => 'satisfied']);
            } else {
                $obligation->update(['status' => 'in_progress']);
            }

            return $schedule;
        });
    }

    /**
     * Recognize point-in-time revenue
     * الاعتراف بالإيرادات في نقطة زمنية
     */
    public function recognizePointInTime(PerformanceObligation $obligation, ?int $fiscalPeriodId = null): ?RevenueRecognitionSchedule
    {
        if ($obligation->satisfaction_pattern !== 'point_in_time') {
            throw new \Exception('This method is for point-in-time obligations only');
        }

        // Recognize full allocated amount
        return $this->recognizeRevenue(
            $obligation,
            $obligation->allocated_transaction_price,
            $fiscalPeriodId
        );
    }

    /**
     * Generate recognition schedule for over-time obligation
     * إنشاء جدول الاعتراف لالتزام على مدى الوقت
     */
    public function generateRecognitionSchedule(PerformanceObligation $obligation): array
    {
        if ($obligation->satisfaction_pattern !== 'over_time') {
            return [];
        }

        $schedule = [];
        $startDate = Carbon::parse($obligation->revenueContract->start_date);
        $endDate = Carbon::parse($obligation->expected_completion_date ?? $obligation->revenueContract->end_date);
        $months = $startDate->diffInMonths($endDate) + 1;
        
        $monthlyAmount = $obligation->allocated_transaction_price / $months;

        $currentDate = $startDate->copy();
        $cumulative = 0;

        for ($i = 0; $i < $months; $i++) {
            $amount = $i === $months - 1 
                ? $obligation->allocated_transaction_price - $cumulative  // Last period gets remainder
                : round($monthlyAmount, 2);
            
            $cumulative += $amount;

            $schedule[] = [
                'period' => $i + 1,
                'date' => $currentDate->format('Y-m-d'),
                'amount' => $amount,
                'cumulative' => $cumulative,
                'percentage' => round(($cumulative / $obligation->allocated_transaction_price) * 100, 2),
            ];

            $currentDate->addMonth();
        }

        return $schedule;
    }

    /**
     * Create scheduled recognition entries
     * إنشاء قيود الاعتراف المجدولة
     */
    public function createScheduledRecognitions(PerformanceObligation $obligation): void
    {
        $schedule = $this->generateRecognitionSchedule($obligation);

        foreach ($schedule as $item) {
            RevenueRecognitionSchedule::create([
                'performance_obligation_id' => $obligation->id,
                'recognition_date' => $item['date'],
                'amount' => $item['amount'],
                'cumulative_recognized' => $item['cumulative'],
                'status' => 'scheduled',
                'created_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Resolve variable consideration
     * حل المقابل المتغير
     */
    public function resolveVariableConsideration(VariableConsideration $consideration, float $actualAmount): void
    {
        $consideration->update([
            'actual_amount' => $actualAmount,
            'resolution_date' => now(),
            'status' => 'resolved',
        ]);

        // Reallocate transaction price
        $this->allocateTransactionPrice($consideration->revenueContract);
    }

    /**
     * Get contract revenue summary
     * الحصول على ملخص إيرادات العقد
     */
    public function getContractSummary(RevenueContract $contract): array
    {
        $obligations = $contract->performanceObligations;
        $totalAllocated = $obligations->sum('allocated_transaction_price');
        $totalRecognized = 0;
        
        foreach ($obligations as $obligation) {
            $totalRecognized += $obligation->schedules()
                ->where('status', 'recognized')
                ->sum('amount');
        }

        return [
            'contract_number' => $contract->contract_number,
            'total_transaction_price' => $contract->total_transaction_price,
            'total_allocated' => $totalAllocated,
            'total_recognized' => $totalRecognized,
            'remaining_to_recognize' => $totalAllocated - $totalRecognized,
            'recognition_percentage' => $totalAllocated > 0 
                ? round(($totalRecognized / $totalAllocated) * 100, 2) 
                : 0,
            'obligations' => $obligations->map(function ($ob) {
                $recognized = $ob->schedules()->where('status', 'recognized')->sum('amount');
                return [
                    'name' => $ob->name,
                    'status' => $ob->status,
                    'allocated' => $ob->allocated_transaction_price,
                    'recognized' => $recognized,
                    'remaining' => $ob->allocated_transaction_price - $recognized,
                ];
            }),
        ];
    }

    private function generateContractNumber(): string
    {
        $prefix = 'RC';
        $year = date('Y');
        $lastContract = RevenueContract::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
        $sequence = $lastContract ? intval(substr($lastContract->contract_number, -5)) + 1 : 1;
        return $prefix . $year . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }
}
