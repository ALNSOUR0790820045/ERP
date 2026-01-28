<?php

namespace App\Filament\Widgets;

use App\Models\EvmMeasurement;
use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EvmDashboardWidget extends BaseWidget
{
    protected static ?int $sort = 15;

    public function getHeading(): ?string
    {
        return 'مؤشرات القيمة المكتسبة (EVM)';
    }

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // الحصول على أحدث قياسات EVM للمشاريع النشطة
        $latestMeasurements = EvmMeasurement::query()
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
        $avgSpi = $latestMeasurements->avg('schedule_performance_index');
        $avgCpi = $latestMeasurements->avg('cost_performance_index');
        
        // المشاريع المتأخرة
        $behindSchedule = $latestMeasurements->where('schedule_performance_index', '<', 0.90)->count();
        
        // المشاريع التي تتجاوز الميزانية
        $overBudget = $latestMeasurements->where('cost_performance_index', '<', 0.90)->count();
        
        // المشاريع الحرجة
        $criticalProjects = $latestMeasurements->where('overall_status', 'red')->count();

        // إجمالي الفرق المتوقع
        $totalVac = $latestMeasurements->sum('variance_at_completion');

        return [
            Stat::make('متوسط SPI', number_format($avgSpi, 2))
                ->description('مؤشر أداء الجدول الزمني')
                ->descriptionIcon($avgSpi >= 0.95 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($avgSpi >= 0.95 ? 'success' : ($avgSpi >= 0.80 ? 'warning' : 'danger'))
                ->chart($this->getSpiTrend()),

            Stat::make('متوسط CPI', number_format($avgCpi, 2))
                ->description('مؤشر أداء التكلفة')
                ->descriptionIcon($avgCpi >= 0.95 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($avgCpi >= 0.95 ? 'success' : ($avgCpi >= 0.80 ? 'warning' : 'danger'))
                ->chart($this->getCpiTrend()),

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

            Stat::make('الفرق المتوقع (VAC)', number_format($totalVac, 0) . ' JOD')
                ->description($totalVac >= 0 ? 'وفر متوقع' : 'تجاوز متوقع')
                ->descriptionIcon($totalVac >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($totalVac >= 0 ? 'success' : 'danger'),
        ];
    }

    protected function getSpiTrend(): array
    {
        $measurements = EvmMeasurement::query()
            ->where('status', 'approved')
            ->orderBy('measurement_date')
            ->limit(10)
            ->pluck('schedule_performance_index')
            ->toArray();

        return array_map(fn($v) => $v * 100, $measurements);
    }

    protected function getCpiTrend(): array
    {
        $measurements = EvmMeasurement::query()
            ->where('status', 'approved')
            ->orderBy('measurement_date')
            ->limit(10)
            ->pluck('cost_performance_index')
            ->toArray();

        return array_map(fn($v) => $v * 100, $measurements);
    }
}
