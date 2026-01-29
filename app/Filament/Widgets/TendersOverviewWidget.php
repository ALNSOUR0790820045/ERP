<?php

namespace App\Filament\Widgets;

use App\Models\Tender;
use App\Models\Tenders\TenderAlert;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class TendersOverviewWidget extends BaseWidget
{
    protected ?string $heading = 'ملخص العطاءات';
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $tenderModel = Tender::class;
        
        // إحصائيات العطاءات
        $totalTenders = $tenderModel::count();
        $activeTenders = $tenderModel::where('status', 'active')
            ->orWhere('status', 'in_progress')
            ->count();
        $pendingSubmission = $tenderModel::where('status', 'draft')
            ->orWhere('status', 'pending')
            ->count();
        $wonTenders = $tenderModel::where('status', 'won')->count();
        $lostTenders = $tenderModel::where('status', 'lost')->count();
        
        // تنبيهات العطاءات العاجلة
        $urgentAlerts = TenderAlert::where('priority', 'urgent')
            ->where('is_active', true)
            ->whereNull('resolved_at')
            ->count();

        // مواعيد الإغلاق القريبة (7 أيام)
        $upcomingDeadlines = $tenderModel::where('submission_deadline', '>=', now())
            ->where('submission_deadline', '<=', now()->addDays(7))
            ->whereIn('status', ['active', 'in_progress', 'pending'])
            ->count();

        return [
            Stat::make('إجمالي العطاءات', $totalTenders)
                ->description('جميع العطاءات في النظام')
                ->icon('heroicon-o-document-text')
                ->color('gray'),

            Stat::make('العطاءات النشطة', $activeTenders)
                ->description('قيد التجهيز')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('مواعيد قريبة', $upcomingDeadlines)
                ->description('خلال 7 أيام')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($upcomingDeadlines > 0 ? 'danger' : 'success'),

            Stat::make('العطاءات الفائزة', $wonTenders)
                ->description('تم الفوز بها')
                ->icon('heroicon-o-trophy')
                ->color('success'),

            Stat::make('العطاءات الخاسرة', $lostTenders)
                ->description('لم يتم الفوز')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('تنبيهات عاجلة', $urgentAlerts)
                ->description('تحتاج اهتمام')
                ->icon('heroicon-o-bell-alert')
                ->color($urgentAlerts > 0 ? 'danger' : 'success'),
        ];
    }
}
