<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BankReconciliationWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = true;
    protected static ?string $pollingInterval = null;
    
    protected static ?int $sort = 5;
    
    public function getHeading(): ?string
    {
        return 'التسويات البنكية';
    }

    protected function getStats(): array
    {
        if (!Schema::hasTable('bank_accounts')) {
            return [
                Stat::make('الحسابات البنكية', '0')
                    ->description('لا توجد بيانات')
                    ->color('gray'),
            ];
        }
        
        // عدد الحسابات البنكية النشطة
        $activeBankAccounts = DB::table('bank_accounts')
            ->where('is_active', true)
            ->count();
        
        // آخر تسوية
        $lastReconciliation = null;
        $unreconciledDifferences = 0;
        $accountsNeedReconciliation = 0;
        
        if (Schema::hasTable('bank_reconciliations')) {
            $lastReconciliation = DB::table('bank_reconciliations')
                ->orderBy('reconciliation_date', 'desc')
                ->first();
            
            $unreconciledDifferences = DB::table('bank_reconciliations')
                ->where('status', 'pending')
                ->sum('difference_amount') ?? 0;
            
            // الحسابات التي تحتاج تسوية
            $reconciledThisMonth = DB::table('bank_reconciliations')
                ->whereMonth('reconciliation_date', now()->month)
                ->whereYear('reconciliation_date', now()->year)
                ->pluck('bank_account_id')
                ->toArray();
            
            $accountsNeedReconciliation = DB::table('bank_accounts')
                ->where('is_active', true)
                ->whereNotIn('id', $reconciledThisMonth)
                ->count();
        }
        
        return [
            Stat::make('الحسابات البنكية', $activeBankAccounts)
                ->description('حسابات نشطة')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('primary'),
            
            Stat::make('آخر تسوية', $lastReconciliation?->reconciliation_date ?? 'لا يوجد')
                ->description('')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
            
            Stat::make('تحتاج تسوية', $accountsNeedReconciliation)
                ->description('حسابات لم تُسوَّ هذا الشهر')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($accountsNeedReconciliation > 0 ? 'warning' : 'success'),
            
            Stat::make('فروقات معلقة', number_format(abs($unreconciledDifferences)) . ' د.أ')
                ->description('بحاجة للمراجعة')
                ->descriptionIcon('heroicon-m-calculator')
                ->color($unreconciledDifferences != 0 ? 'danger' : 'success'),
        ];
    }
}
