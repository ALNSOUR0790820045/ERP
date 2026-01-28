<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use Illuminate\Support\Facades\DB;

class FixedAssetsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;
    
    public function getHeading(): ?string
    {
        return 'ملخص الأصول الثابتة';
    }

    protected function getStats(): array
    {
        // إجمالي الأصول
        $totalAssets = FixedAsset::count();
        
        // إجمالي القيمة الدفترية
        $totalBookValue = FixedAsset::where('status', 'active')
            ->selectRaw('SUM(acquisition_cost - COALESCE(accumulated_depreciation, 0)) as book_value')
            ->value('book_value') ?? 0;
        
        // إجمالي الإهلاك المتراكم
        $totalDepreciation = FixedAsset::where('status', 'active')
            ->sum('accumulated_depreciation') ?? 0;
        
        // أصول تحتاج صيانة
        $assetsNeedingMaintenance = FixedAsset::where('status', 'active')
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
