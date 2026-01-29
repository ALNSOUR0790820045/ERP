<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemOverviewWidget extends BaseWidget
{
    protected ?string $heading = 'نظرة عامة على النظام';
    protected static ?int $sort = 0;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $companyName = 'غير محدد';
        $legalName = '';
        if (Schema::hasTable('companies')) {
            $company = DB::table('companies')->first();
            $companyName = $company?->name_ar ?? $company?->name ?? 'غير محدد';
            $legalName = $company?->legal_name ?? '';
        }
        
        $usersCount = Schema::hasTable('users') ? DB::table('users')->count() : 0;
        $projectsCount = Schema::hasTable('projects') ? DB::table('projects')->count() : 0;
        $tendersCount = Schema::hasTable('tenders') ? DB::table('tenders')->count() : 0;
        
        return [
            Stat::make('الشركة', $companyName)
                ->description($legalName)
                ->icon('heroicon-o-building-office-2')
                ->color('primary'),

            Stat::make('المستخدمين', $usersCount)
                ->description('المستخدمين النشطين')
                ->icon('heroicon-o-users')
                ->color('info'),

            Stat::make('المشاريع', $projectsCount)
                ->description('إجمالي المشاريع')
                ->icon('heroicon-o-briefcase')
                ->color('success'),

            Stat::make('العطاءات', $tendersCount)
                ->description('إجمالي العطاءات')
                ->icon('heroicon-o-document-text')
                ->color('warning'),
        ];
    }
}
