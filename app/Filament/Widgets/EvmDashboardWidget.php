<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EvmDashboardWidget extends BaseWidget
{
    protected static bool $isLazy = true;
    protected static ?string $pollingInterval = null;
    
    protected static ?int $sort = 15;

    public function getHeading(): ?string
    {
        return 'مؤشرات القيمة المكتسبة (EVM)';
    }

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        if (!Schema::hasTable('evm_measurements')) {
            return [
                Stat::make('قياسات EVM', '0')
                    ->description('لا توجد بيانات')
                    ->color('gray'),
            ];
        }

        // الحصول على أحدث قياسات EVM للمشاريع النشطة
        $latestMeasurements = DB::table('evm_measurements')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('evm_measurements')
                    ->where('status', 'approved')
                    ->groupBy('project_id');
            })
            ->get();

        if ($latestMeasurements->isEmpty()) {
            return [
                Stat::make('قياسات EVM', '0')
                    ->description('لا توجد قياسات معتمدة')
                    ->color('gray'),
            ];
        }

        // حساب المتوسطات
        $avgSpi = $latestMeasurements->avg('schedule_performance_index') ?? 0;
        $avgCpi = $latestMeasurements->avg('cost_performance_index') ?? 0;
        
        // المشاريع المتأخرة
        $behindSchedule = $latestMeasurements->where('schedule_performance_index', '<', 0.90)->count();
        
        // المشاريع التي تتجاوز الميزانية
        $overBudget = $latestMeasurements->where('cost_performance_index', '<', 0.90)->count();
        
        // المشاريع الحرجة
        $criticalProjects = $latestMeasurements->where('overall_status', 'red')->count();

        // إجمالي الفرق المتوقع
        $totalVac = $latestMeasurements->sum('variance_at_completion') ?? 0;

        return [
            Stat::make('متوسط SPI', number_format($avgSpi, 2))
                ->description('مؤشر أداء الجدول الزمني')
                ->descriptionIcon($avgSpi >= 0.95 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($avgSpi >= 0.95 ? 'success' : ($avgSpi >= 0.80 ? 'warning' : 'danger')),

            Stat::make('متوسط CPI', number_format($avgCpi, 2))
                ->description('مؤشر أداء التكلفة')
                ->descriptionIcon($avgCpi >= 0.95 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($avgCpi >= 0.95 ? 'success' : ($avgCpi >= 0.80 ? 'warning' : 'danger')),

            Stat::make('متأخرة عن الجدول', $behindSchedule)
                ->description('مشاريع SPI < 0.90')
                ->descriptionIcon('heroicon-m-clock')
                ->color($behindSchedule > 0 ? 'warning' : 'success'),

            Stat::make('تتجاوز الميزانية', $overBudget)
                ->description('مشاريع CPI < 0.90')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($overBudget > 0 ? 'warning' : 'success'),

            Stat::make('مشاريع حرجة', $criticalProjects)
                ->description('تحتاج تدخل فوري')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($criticalProjects > 0 ? 'danger' : 'success'),

            Stat::make('الفرق المتوقع (VAC)', number_format($totalVac, 0) . ' د.أ')
                ->description($totalVac >= 0 ? 'وفر متوقع' : 'تجاوز متوقع')
                ->descriptionIcon($totalVac >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($totalVac >= 0 ? 'success' : 'danger'),
        ];
    }
}
