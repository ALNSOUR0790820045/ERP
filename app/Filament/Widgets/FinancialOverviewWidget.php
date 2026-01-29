<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Contract;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinancialOverviewWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = true;
    protected static ?string $pollingInterval = null;
    
    protected static ?int $sort = 1;
    
    protected int|string|array $columnSpan = 'full';
    
    public function getHeading(): ?string
    {
        return 'نظرة عامة مالية';
    }

    protected function getStats(): array
    {
        // إجمالي قيمة العقود النشطة
        $totalContracts = Schema::hasTable('contracts') 
            ? (Contract::where('status', 'active')->sum('contract_value') ?? 0)
            : 0;
        
        // إجمالي المستخلصات المعتمدة هذا الشهر
        $monthlyIPC = 0;
        if (Schema::hasTable('progress_certificates')) {
            $monthlyIPC = DB::table('progress_certificates')
                ->where('status', 'approved')
                ->whereMonth('approval_date', now()->month)
                ->whereYear('approval_date', now()->year)
                ->sum('net_amount') ?? 0;
        }
        
        // المستخلصات المعلقة
        $pendingIPC = 0;
        if (Schema::hasTable('progress_certificates')) {
            $pendingIPC = DB::table('progress_certificates')
                ->where('status', 'pending')
                ->count();
        }
        
        // إجمالي الإيرادات هذا العام
        $yearlyRevenue = Schema::hasTable('invoices')
            ? (Invoice::whereYear('invoice_date', now()->year)
                ->where('status', 'paid')
                ->sum('total_amount') ?? 0)
            : 0;
        
        return [
            Stat::make('العقود النشطة', number_format($totalContracts) . ' د.أ')
                ->description('إجمالي قيمة العقود النشطة')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5]),
            
            Stat::make('المستخلصات هذا الشهر', number_format($monthlyIPC) . ' د.أ')
                ->description('المستخلصات المعتمدة')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),
            
            Stat::make('مستخلصات معلقة', $pendingIPC)
                ->description('بانتظار الموافقة')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingIPC > 5 ? 'danger' : 'warning'),
            
            Stat::make('الإيرادات السنوية', number_format($yearlyRevenue) . ' د.أ')
                ->description('إجمالي الإيرادات لعام ' . now()->year)
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart([3, 5, 4, 7, 6, 8, 5]),
        ];
    }
}
