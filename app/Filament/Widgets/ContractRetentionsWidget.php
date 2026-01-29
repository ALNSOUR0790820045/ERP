<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ContractRetentionsWidget extends ChartWidget
{
    protected static ?int $sort = 4;
    
    protected static ?string $maxHeight = '300px';
    
    public function getHeading(): ?string
    {
        return 'محتجزات العقود';
    }

    protected function getData(): array
    {
        if (!Schema::hasTable('contract_retentions')) {
            return [
                'datasets' => [['data' => [0], 'backgroundColor' => ['rgb(156, 163, 175)']]],
                'labels' => ['لا توجد بيانات'],
            ];
        }
        
        // الحصول على محتجزات العقود حسب الحالة
        $retentions = DB::table('contract_retentions')
            ->select('status', DB::raw('SUM(amount) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();
        
        $labels = [];
        $data = [];
        $colors = [];
        
        $statusMap = [
            'held' => ['label' => 'محتجزة', 'color' => 'rgb(245, 158, 11)'],
            'pending_release' => ['label' => 'بانتظار الإفراج', 'color' => 'rgb(59, 130, 246)'],
            'released' => ['label' => 'مُفرج عنها', 'color' => 'rgb(34, 197, 94)'],
            'forfeited' => ['label' => 'مصادرة', 'color' => 'rgb(239, 68, 68)'],
        ];
        
        foreach ($retentions as $status => $total) {
            if (isset($statusMap[$status])) {
                $labels[] = $statusMap[$status]['label'];
                $data[] = $total;
                $colors[] = $statusMap[$status]['color'];
            }
        }
        
        if (empty($data)) {
            return [
                'datasets' => [['data' => [0], 'backgroundColor' => ['rgb(156, 163, 175)']]],
                'labels' => ['لا توجد محتجزات'],
            ];
        }
        
        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                ],
            ],
        ];
    }
}
