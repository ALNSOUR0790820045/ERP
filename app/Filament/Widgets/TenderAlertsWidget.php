<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class TenderAlertsWidget extends Widget
{
    protected static bool $isLazy = true;
    
    protected static ?string $heading = 'تنبيهات العطاءات';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;
    
    protected static string $view = 'filament.widgets.tender-alerts-widget';
    
    public function getAlerts(): array
    {
        if (!Schema::hasTable('tender_alerts')) {
            return [];
        }
        
        return DB::table('tender_alerts')
            ->where('is_active', true)
            ->whereNull('resolved_at')
            ->orderBy('priority')
            ->orderByDesc('alert_date')
            ->limit(5)
            ->get()
            ->toArray();
    }
}
