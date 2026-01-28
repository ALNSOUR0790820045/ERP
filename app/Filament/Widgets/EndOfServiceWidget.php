<?php

namespace App\Filament\Widgets;

use App\Models\EndOfServiceProvision;
use App\Models\EndOfServiceCalculation;
use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

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
        $currentMonth = date('m');

        // إجمالي المخصصات
        $totalProvisions = EndOfServiceProvision::query()
            ->where('year', $currentYear)
            ->sum('closing_balance');

        // الحسابات المعلقة
        $pendingCalculations = EndOfServiceCalculation::query()
            ->where('status', 'pending_approval')
            ->count();

        // المبالغ المعتمدة غير المدفوعة
        $approvedUnpaid = EndOfServiceCalculation::query()
            ->where('status', 'approved')
            ->sum('net_entitlement');

        // المبالغ المدفوعة هذا العام
        $paidThisYear = EndOfServiceCalculation::query()
            ->where('status', 'paid')
            ->whereYear('payment_date', $currentYear)
            ->sum('net_entitlement');

        // متوسط سنوات الخدمة
        $avgServiceYears = EndOfServiceCalculation::query()
            ->whereYear('created_at', $currentYear)
            ->avg('total_service_years');

        return [
            Stat::make('إجمالي المخصصات', number_format($totalProvisions, 0) . ' JOD')
                ->description("مخصصات {$currentYear}")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('حسابات معلقة', $pendingCalculations)
                ->description('بانتظار الموافقة')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingCalculations > 0 ? 'warning' : 'success'),

            Stat::make('معتمدة غير مدفوعة', number_format($approvedUnpaid, 0) . ' JOD')
                ->description('تحتاج دفع')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color($approvedUnpaid > 0 ? 'warning' : 'success'),

            Stat::make('المدفوع هذا العام', number_format($paidThisYear, 0) . ' JOD')
                ->description("خلال {$currentYear}")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
