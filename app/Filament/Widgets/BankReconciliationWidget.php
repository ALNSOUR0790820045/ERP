<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use Illuminate\Support\Facades\DB;

class BankReconciliationWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 5;
    
    public function getHeading(): ?string
    {
        return 'التسويات البنكية';
    }

    protected function getStats(): array
    {
        // عدد الحسابات البنكية النشطة
        $activeBankAccounts = BankAccount::where('is_active', true)->count();
        
        // آخر تسوية
        $lastReconciliation = BankReconciliation::latest('reconciliation_date')
            ->first();
        
        // الحسابات التي تحتاج تسوية (لم تُسوَّ هذا الشهر)
        $accountsNeedReconciliation = BankAccount::where('is_active', true)
            ->whereDoesntHave('reconciliations', function ($query) {
                $query->whereMonth('reconciliation_date', now()->month)
                    ->whereYear('reconciliation_date', now()->year);
            })
            ->count();
        
        // إجمالي الفروقات غير المطابقة
        $unreconciledDifferences = BankReconciliation::where('status', 'pending')
            ->sum('difference_amount') ?? 0;
        
        return [
            Stat::make('الحسابات البنكية', $activeBankAccounts)
                ->description('حسابات نشطة')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('primary'),
            
            Stat::make('آخر تسوية', $lastReconciliation?->reconciliation_date?->format('Y-m-d') ?? 'لا يوجد')
                ->description($lastReconciliation?->bankAccount?->account_name ?? '')
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
