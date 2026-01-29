<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HRPayrollWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 6;
    
    public function getHeading(): ?string
    {
        return 'الموارد البشرية والرواتب';
    }

    protected function getStats(): array
    {
        // عدد الموظفين النشطين
        $activeEmployees = Schema::hasTable('employees') 
            ? DB::table('employees')->where('status', 'active')->count() 
            : 0;
        
        // إجمالي الرواتب الشهرية
        $monthlyPayroll = 0;
        if (Schema::hasTable('payrolls')) {
            $monthlyPayroll = DB::table('payrolls')
                ->whereMonth('payroll_date', now()->month)
                ->whereYear('payroll_date', now()->year)
                ->sum('net_salary') ?? 0;
        }
        
        // اشتراكات الضمان الاجتماعي
        $socialSecurityRate = 21.75;
        if (Schema::hasTable('social_security_settings')) {
            $setting = DB::table('social_security_settings')
                ->where('year', now()->year)
                ->where('is_active', true)
                ->first();
            if ($setting) {
                $socialSecurityRate = ($setting->employer_rate ?? 0) + ($setting->employee_rate ?? 0);
            }
        }
        
        // الموظفون الجدد هذا الشهر
        $newEmployees = Schema::hasTable('employees') 
            ? DB::table('employees')
                ->whereMonth('hire_date', now()->month)
                ->whereYear('hire_date', now()->year)
                ->count() 
            : 0;
        
        return [
            Stat::make('الموظفون النشطون', number_format($activeEmployees))
                ->description('إجمالي الموظفين')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            
            Stat::make('رواتب الشهر', number_format($monthlyPayroll) . ' د.أ')
                ->description('صافي الرواتب المستحقة')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            
            Stat::make('الضمان الاجتماعي', $socialSecurityRate . '%')
                ->description('نسبة الاشتراك الإجمالية')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('info'),
            
            Stat::make('موظفون جدد', $newEmployees)
                ->description('هذا الشهر')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),
        ];
    }
}
