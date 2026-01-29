<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MonthlyRevenueChart extends ChartWidget
{
    protected static bool $isLazy = true;
    protected static ?string $pollingInterval = null;
    
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
        
        $hasProgressCertificates = Schema::hasTable('progress_certificates');
        $hasInvoices = Schema::hasTable('invoices');
        
        // الحصول على بيانات آخر 12 شهر
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->translatedFormat('M Y');
            
            // المستخلصات المعتمدة
            $ipcTotal = 0;
            if ($hasProgressCertificates) {
                $ipcTotal = DB::table('progress_certificates')
                    ->where('status', 'approved')
                    ->whereMonth('approval_date', $date->month)
                    ->whereYear('approval_date', $date->year)
                    ->sum('net_amount') ?? 0;
            }
            $ipcData[] = $ipcTotal;
            
            // الفواتير المحصلة
            $invoiceTotal = 0;
            if ($hasInvoices) {
                $invoiceTotal = DB::table('invoices')
                    ->where('status', 'paid')
                    ->whereMonth('payment_date', $date->month)
                    ->whereYear('payment_date', $date->year)
                    ->sum('total_amount') ?? 0;
            }
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
