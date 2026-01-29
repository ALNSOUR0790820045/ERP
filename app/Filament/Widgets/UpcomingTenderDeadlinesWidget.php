<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpcomingTenderDeadlinesWidget extends Widget
{
    protected static bool $isLazy = true;
    
    protected static ?string $heading = 'مواعيد الإغلاق القريبة';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;
    
    protected static string $view = 'filament.widgets.upcoming-tender-deadlines-widget';

    public function getDeadlines(): array
    {
        if (!Schema::hasTable('tenders')) {
            return [];
        }
        
        return DB::table('tenders')
            ->where('submission_deadline', '>=', now())
            ->where('submission_deadline', '<=', now()->addDays(14))
            ->whereIn('status', ['active', 'in_progress', 'pending', 'draft'])
            ->orderBy('submission_deadline')
            ->limit(5)
            ->get()
            ->toArray();
    }
}
