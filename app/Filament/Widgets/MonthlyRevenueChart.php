<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\ProgressCertificate;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class MonthlyRevenueChart extends ChartWidget
{
    protected static ?int $sort = 7;
    
    protected static ?string $maxHeight = '300px';
    
    protected int|string|array $columnSpan = 'full';
    
    public function getHeading(): ?string
    {
        return 'الإيرادات الشهرية';
    }

    protected function getData(): array
    {
        $months = [];
        $ipcData = [];
        $invoiceData = [];
        
        // الحصول على بيانات آخر 12 شهر
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->translatedFormat('M Y');
            
            // المستخلصات المعتمدة
            $ipcTotal = ProgressCertificate::where('status', 'approved')
                ->whereMonth('approval_date', $date->month)
                ->whereYear('approval_date', $date->year)
                ->sum('net_amount') ?? 0;
            $ipcData[] = $ipcTotal;
            
            // الفواتير المحصلة
            $invoiceTotal = Invoice::where('status', 'paid')
                ->whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->sum('total_amount') ?? 0;
            $invoiceData[] = $invoiceTotal;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'المستخلصات',
                    'data' => $ipcData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'الفواتير',
                    'data' => $invoiceData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $months,
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
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return value.toLocaleString() + ' د.أ'; }",
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
            ],
        ];
    }
}
