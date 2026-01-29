<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Project;
use App\Models\Tender;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemOverviewWidget extends BaseWidget
{
    protected ?string $heading = 'نظرة عامة على النظام';
    protected static ?int $sort = 0;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $company = Company::first();
        
        return [
            Stat::make('الشركة', $company?->name_ar ?? 'غير محدد')
                ->description($company?->legal_name ?? '')
                ->icon('heroicon-o-building-office-2')
                ->color('primary'),

            Stat::make('المستخدمين', User::count())
                ->description('المستخدمين النشطين')
                ->icon('heroicon-o-users')
                ->color('info'),

            Stat::make('المشاريع', class_exists(Project::class) ? Project::count() : 0)
                ->description('إجمالي المشاريع')
                ->icon('heroicon-o-briefcase')
                ->color('success'),

            Stat::make('العطاءات', Tender::count())
                ->description('إجمالي العطاءات')
                ->icon('heroicon-o-document-text')
                ->color('warning'),
        ];
    }
}
