<?php

namespace App\Filament\Widgets;

use App\Models\EvmMeasurement;
use Filament\Widgets\ChartWidget;

class EvmPerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'أداء المشاريع (SPI vs CPI)';

    protected static ?int $sort = 16;

    protected int | string | array $columnSpan = 2;

    protected function getData(): array
    {
        // الحصول على أحدث 6 قياسات
        $measurements = EvmMeasurement::query()
            ->with('project')
            ->where('status', 'approved')
            ->orderByDesc('measurement_date')
            ->limit(6)
            ->get()
            ->reverse();

        $labels = $measurements->map(fn($m) => $m->measurement_date->format('Y-m-d'))->toArray();
        $spiData = $measurements->pluck('schedule_performance_index')->toArray();
        $cpiData = $measurements->pluck('cost_performance_index')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'SPI (أداء الجدول)',
                    'data' => $spiData,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'CPI (أداء التكلفة)',
                    'data' => $cpiData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'الحد المقبول',
                    'data' => array_fill(0, count($labels), 1),
                    'borderColor' => '#ef4444',
                    'borderDash' => [5, 5],
                    'fill' => false,
                    'pointRadius' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'min' => 0,
                    'max' => 1.5,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
