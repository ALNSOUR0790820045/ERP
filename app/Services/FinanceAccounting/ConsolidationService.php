<?php

namespace App\Services\FinanceAccounting;

use App\Models\FinanceAccounting\ConsolidationGroup;
use App\Models\FinanceAccounting\ConsolidationEntity;
use App\Models\FinanceAccounting\ConsolidationRun;
use App\Models\FinanceAccounting\IntercompanyTransaction;
use App\Models\FinanceAccounting\JournalVoucher;
use App\Models\FinanceAccounting\JournalVoucherLine;
use App\Models\FinanceAccounting\ChartOfAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Financial Consolidation Service
 * خدمة التجميع المالي
 */
class ConsolidationService
{
    /**
     * Run consolidation for a group and period
     * تنفيذ التجميع لمجموعة وفترة
     */
    public function runConsolidation(ConsolidationGroup $group, int $fiscalPeriodId): ConsolidationRun
    {
        return DB::transaction(function () use ($group, $fiscalPeriodId) {
            // Create consolidation run
            $run = ConsolidationRun::create([
                'consolidation_group_id' => $group->id,
                'fiscal_period_id' => $fiscalPeriodId,
                'run_number' => ConsolidationRun::generateRunNumber(),
                'consolidation_date' => now(),
                'status' => 'processing',
                'created_by' => auth()->id(),
            ]);

            try {
                // Step 1: Get exchange rates
                $exchangeRates = $this->getExchangeRates($group, now());
                $run->update(['exchange_rates_used' => $exchangeRates]);

                // Step 2: Collect trial balances from all entities
                $trialBalances = $this->collectTrialBalances($group, $fiscalPeriodId);

                // Step 3: Currency translation
                $translatedBalances = $this->translateCurrencies($trialBalances, $exchangeRates, $group->reporting_currency);
                $run->update(['translation_adjustments' => $translatedBalances['adjustments']]);

                // Step 4: Identify intercompany transactions
                $intercompanyTxns = $this->identifyIntercompanyTransactions($group, $fiscalPeriodId);
                
                // Step 5: Create elimination entries
                $eliminations = $this->createEliminationEntries($intercompanyTxns, $run);
                $run->update(['elimination_entries' => $eliminations]);

                // Step 6: Calculate consolidated totals
                $consolidatedTotals = $this->calculateConsolidatedTotals($translatedBalances['balances'], $eliminations);
                
                $run->update([
                    'total_assets' => $consolidatedTotals['assets'],
                    'total_liabilities' => $consolidatedTotals['liabilities'],
                    'total_equity' => $consolidatedTotals['equity'],
                    'net_income' => $consolidatedTotals['net_income'],
                    'status' => 'completed',
                ]);

                return $run;

            } catch (\Exception $e) {
                $run->update([
                    'status' => 'error',
                    'notes' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Get exchange rates for consolidation
     * الحصول على أسعار الصرف للتجميع
     */
    public function getExchangeRates(ConsolidationGroup $group, Carbon $date): array
    {
        $rates = [];
        $reportingCurrency = $group->reporting_currency;

        foreach ($group->entities as $entity) {
            $entityCurrency = $entity->company->currency_code ?? 'JOD';
            
            if ($entityCurrency !== $reportingCurrency) {
                // Get current rate and average rate
                $rates[$entity->company_id] = [
                    'currency' => $entityCurrency,
                    'current_rate' => $this->getCurrentExchangeRate($entityCurrency, $reportingCurrency),
                    'average_rate' => $this->getAverageExchangeRate($entityCurrency, $reportingCurrency, $date),
                    'historical_rate' => $this->getHistoricalExchangeRate($entityCurrency, $reportingCurrency),
                ];
            } else {
                $rates[$entity->company_id] = [
                    'currency' => $entityCurrency,
                    'current_rate' => 1,
                    'average_rate' => 1,
                    'historical_rate' => 1,
                ];
            }
        }

        return $rates;
    }

    /**
     * Collect trial balances from all entities
     * جمع ميزان المراجعة من جميع الكيانات
     */
    public function collectTrialBalances(ConsolidationGroup $group, int $fiscalPeriodId): array
    {
        $balances = [];

        foreach ($group->entities as $entity) {
            $balances[$entity->company_id] = $this->getEntityTrialBalance($entity->company_id, $fiscalPeriodId);
        }

        return $balances;
    }

    /**
     * Get entity trial balance
     * الحصول على ميزان مراجعة الكيان
     */
    private function getEntityTrialBalance(int $companyId, int $fiscalPeriodId): array
    {
        return ChartOfAccount::where('company_id', $companyId)
            ->with(['journalLines' => function ($query) use ($fiscalPeriodId) {
                $query->whereHas('journalVoucher', function ($q) use ($fiscalPeriodId) {
                    $q->where('fiscal_period_id', $fiscalPeriodId)
                      ->where('status', 'posted');
                });
            }])
            ->get()
            ->map(function ($account) {
                $debit = $account->journalLines->sum('debit');
                $credit = $account->journalLines->sum('credit');
                
                return [
                    'account_id' => $account->id,
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'account_type' => $account->type,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $debit - $credit,
                ];
            })
            ->toArray();
    }

    /**
     * Translate currencies
     * تحويل العملات
     */
    public function translateCurrencies(array $trialBalances, array $exchangeRates, string $reportingCurrency): array
    {
        $translatedBalances = [];
        $translationAdjustments = [];

        foreach ($trialBalances as $companyId => $accounts) {
            $rates = $exchangeRates[$companyId] ?? ['current_rate' => 1, 'average_rate' => 1, 'historical_rate' => 1];
            
            $companyAdjustment = 0;
            $translatedBalances[$companyId] = [];

            foreach ($accounts as $account) {
                // Apply different rates based on account type
                $rate = match($account['account_type']) {
                    'asset', 'liability' => $rates['current_rate'],
                    'revenue', 'expense' => $rates['average_rate'],
                    'equity' => $rates['historical_rate'],
                    default => $rates['current_rate'],
                };

                $translatedBalance = $account['balance'] * $rate;
                $originalBalance = $account['balance'];
                
                $translatedBalances[$companyId][] = array_merge($account, [
                    'translated_balance' => round($translatedBalance, 2),
                    'exchange_rate' => $rate,
                ]);

                // Track translation adjustment for equity
                if ($account['account_type'] === 'equity') {
                    $companyAdjustment += ($translatedBalance - $originalBalance);
                }
            }

            $translationAdjustments[$companyId] = round($companyAdjustment, 2);
        }

        return [
            'balances' => $translatedBalances,
            'adjustments' => $translationAdjustments,
        ];
    }

    /**
     * Identify intercompany transactions
     * تحديد المعاملات بين الشركات
     */
    public function identifyIntercompanyTransactions(ConsolidationGroup $group, int $fiscalPeriodId): Collection
    {
        $entityCompanyIds = $group->entities->pluck('company_id')->toArray();

        // Find transactions where both from and to companies are in the group
        return IntercompanyTransaction::where(function ($query) use ($entityCompanyIds) {
            $query->whereIn('from_company_id', $entityCompanyIds)
                  ->whereIn('to_company_id', $entityCompanyIds);
        })
        ->whereNull('consolidation_run_id')
        ->where('is_eliminated', false)
        ->get();
    }

    /**
     * Create elimination entries
     * إنشاء قيود الإلغاء
     */
    public function createEliminationEntries(Collection $transactions, ConsolidationRun $run): array
    {
        $eliminations = [];

        foreach ($transactions as $txn) {
            // Mark transaction as linked to this run
            $txn->update(['consolidation_run_id' => $run->id]);

            // Create elimination journal entry
            $elimination = [
                'transaction_id' => $txn->id,
                'from_company' => $txn->from_company_id,
                'to_company' => $txn->to_company_id,
                'amount' => $txn->amount_reporting_currency,
                'type' => $txn->transaction_type,
            ];

            // Mark as eliminated
            $txn->update(['is_eliminated' => true]);

            $eliminations[] = $elimination;
        }

        return $eliminations;
    }

    /**
     * Calculate consolidated totals
     * حساب الإجماليات المجمعة
     */
    public function calculateConsolidatedTotals(array $translatedBalances, array $eliminations): array
    {
        $totals = [
            'assets' => 0,
            'liabilities' => 0,
            'equity' => 0,
            'revenue' => 0,
            'expenses' => 0,
            'net_income' => 0,
        ];

        // Sum all translated balances
        foreach ($translatedBalances as $companyBalances) {
            foreach ($companyBalances as $account) {
                $balance = $account['translated_balance'];
                
                switch ($account['account_type']) {
                    case 'asset':
                        $totals['assets'] += $balance;
                        break;
                    case 'liability':
                        $totals['liabilities'] += abs($balance);
                        break;
                    case 'equity':
                        $totals['equity'] += abs($balance);
                        break;
                    case 'revenue':
                        $totals['revenue'] += abs($balance);
                        break;
                    case 'expense':
                        $totals['expenses'] += $balance;
                        break;
                }
            }
        }

        // Subtract eliminations (simplified - in practice this needs account-level elimination)
        $totalEliminations = array_sum(array_column($eliminations, 'amount'));
        $totals['assets'] -= $totalEliminations / 2;
        $totals['liabilities'] -= $totalEliminations / 2;

        // Calculate net income
        $totals['net_income'] = $totals['revenue'] - $totals['expenses'];

        return array_map(fn($v) => round($v, 2), $totals);
    }

    /**
     * Generate consolidated financial statements
     * إنشاء القوائم المالية المجمعة
     */
    public function generateConsolidatedStatements(ConsolidationRun $run): array
    {
        return [
            'balance_sheet' => [
                'total_assets' => $run->total_assets,
                'total_liabilities' => $run->total_liabilities,
                'total_equity' => $run->total_equity,
            ],
            'income_statement' => [
                'net_income' => $run->net_income,
            ],
            'eliminations' => $run->elimination_entries,
            'translation_adjustments' => $run->translation_adjustments,
            'exchange_rates' => $run->exchange_rates_used,
        ];
    }

    // Helper methods for exchange rates
    private function getCurrentExchangeRate(string $from, string $to): float
    {
        // In production, this would fetch from exchange rate table
        return 1.0;
    }

    private function getAverageExchangeRate(string $from, string $to, Carbon $date): float
    {
        return 1.0;
    }

    private function getHistoricalExchangeRate(string $from, string $to): float
    {
        return 1.0;
    }
}
