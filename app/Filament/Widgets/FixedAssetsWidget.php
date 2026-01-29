<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixedAssetsWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = true;
    protected static ?string $pollingInterval = null;
    
    protected static ?int $sort = 2;
    
    public function getHeading(): ?string
    {
        return 'ملخص الأصول الثابتة';
    }

    protected function getStats(): array
    {
        // التحقق من وجود الجدول
        if (!Schema::hasTable('fixed_assets')) {
            return [
                Stat::make('إجمالي الأصول', '0')
                    ->description('لا توجد بيانات')
                    ->descriptionIcon('heroicon-m-cube')
                    ->color('gray'),
            ];
        }

        // إجمالي الأصول
        $totalAssets = DB::table('fixed_assets')->whereNull('deleted_at')->count();
        
        // إجمالي القيمة الدفترية
        $totalBookValue = DB::table('fixed_assets')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->selectRaw('SUM(acquisition_cost - COALESCE(accumulated_depreciation, 0)) as book_value')
            ->value('book_value') ?? 0;
        
        // إجمالي الإهلاك المتراكم
        $totalDepreciation = DB::table('fixed_assets')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->sum('accumulated_depreciation') ?? 0;
        
        // أصول تحتاج صيانة
        $assetsNeedingMaintenance = DB::table('fixed_assets')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->where('next_maintenance_date', '<=', now()->addDays(30))
            ->count();
        
        return [
            Stat::make('إجمالي الأصول', number_format($totalAssets))
                ->description('عدد الأصول المسجلة')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
            
            Stat::make('القيمة الدفترية', number_format($totalBookValue) . ' د.أ')
                ->description('صافي قيمة الأصول')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            
            Stat::make('الإهلاك المتراكم', number_format($totalDepreciation) . ' د.أ')
                ->description('إجمالي الإهلاك')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning'),
            
            Stat::make('تحتاج صيانة', $assetsNeedingMaintenance)
                ->description('خلال 30 يوم')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color($assetsNeedingMaintenance > 0 ? 'danger' : 'success'),
        ];
    }
}
