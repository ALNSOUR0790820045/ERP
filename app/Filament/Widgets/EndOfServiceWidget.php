<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EndOfServiceWidget extends BaseWidget
{
    protected static ?int $sort = 35;

    public function getHeading(): ?string
    {
        return 'نهاية الخدمة';
    }

    protected function getStats(): array
    {
        $currentYear = date('Y');

        // التحقق من وجود الجداول
        $hasProvisions = Schema::hasTable('end_of_service_provisions');
        $hasCalculations = Schema::hasTable('end_of_service_calculations');

        // إجمالي المخصصات
        $totalProvisions = $hasProvisions 
            ? DB::table('end_of_service_provisions')->where('year', $currentYear)->sum('closing_balance') 
            : 0;

        // الحسابات المعلقة
        $pendingCalculations = $hasCalculations 
            ? DB::table('end_of_service_calculations')->where('status', 'pending_approval')->count() 
            : 0;

        // المبالغ المعتمدة غير المدفوعة
        $approvedUnpaid = $hasCalculations 
            ? DB::table('end_of_service_calculations')->where('status', 'approved')->sum('net_entitlement') 
            : 0;

        // المبالغ المدفوعة هذا العام
        $paidThisYear = $hasCalculations 
            ? DB::table('end_of_service_calculations')->where('status', 'paid')->whereYear('payment_date', $currentYear)->sum('net_entitlement') 
            : 0;

        return [
            Stat::make('إجمالي المخصصات', number_format($totalProvisions, 0) . ' د.أ')
                ->description("مخصصات {$currentYear}")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('حسابات معلقة', $pendingCalculations)
                ->description('بانتظار الموافقة')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingCalculations > 0 ? 'warning' : 'success'),

            Stat::make('معتمدة غير مدفوعة', number_format($approvedUnpaid, 0) . ' د.أ')
                ->description('تحتاج دفع')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color($approvedUnpaid > 0 ? 'warning' : 'success'),

            Stat::make('المدفوع هذا العام', number_format($paidThisYear, 0) . ' د.أ')
                ->description("خلال {$currentYear}")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
