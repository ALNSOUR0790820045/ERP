<?php

namespace App\Filament\Resources\TenderResource\Widgets;

use App\Models\Tender;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class TenderStatsWidget extends BaseWidget
{
    public ?Model $record = null;

    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        $tender = $this->record;
        
        // حساب الفروقات
        $priceDiff = $tender->submitted_price && $tender->estimated_value 
            ? (($tender->submitted_price - $tender->estimated_value) / $tender->estimated_value) * 100 
            : null;
        
        $profitMargin = $tender->submitted_price && $tender->total_cost 
            ? (($tender->submitted_price - $tender->total_cost) / $tender->submitted_price) * 100 
            : null;

        return [
            Stat::make('القيمة التقديرية', $tender->estimated_value ? number_format($tender->estimated_value, 0) . ' JOD' : '-')
                ->description('قيمة العطاء المقدرة')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('السعر المقدم', $tender->submitted_price ? number_format($tender->submitted_price, 0) . ' JOD' : '-')
                ->description($priceDiff !== null ? ($priceDiff >= 0 ? '+' : '') . number_format($priceDiff, 1) . '% من التقديري' : 'لم يحدد بعد')
                ->descriptionIcon('heroicon-m-calculator')
                ->color($priceDiff !== null && $priceDiff > 10 ? 'warning' : 'success'),

            Stat::make('هامش الربح', $profitMargin !== null ? number_format($profitMargin, 1) . '%' : '-')
                ->description($tender->markup_percentage ? 'Markup: ' . $tender->markup_percentage . '%' : 'غير محسوب')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($profitMargin !== null && $profitMargin >= 10 ? 'success' : 'warning'),

            Stat::make('بنود BOQ', $tender->boqItems()->count())
                ->description($tender->boqItems()->sum('total_price') ? number_format($tender->boqItems()->sum('total_price'), 0) . ' JOD' : 'لا توجد بنود')
                ->descriptionIcon('heroicon-m-table-cells')
                ->color('primary'),
        ];
    }
}
