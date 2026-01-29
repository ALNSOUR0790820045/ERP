<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Schema;

class InventoryAlertsWidget extends Widget
{
    protected static bool $isLazy = true;
    
    protected static ?int $sort = 3;
    
    protected int|string|array $columnSpan = 1;
    
    protected static string $view = 'filament.widgets.inventory-alerts-widget';
    
    public function getHeading(): ?string
    {
        return 'تنبيهات المخزون';
    }
    
    public function getAlerts(): array
    {
        if (!Schema::hasTable('inventory_balances') || !Schema::hasTable('items')) {
            return [];
        }
        
        return \Illuminate\Support\Facades\DB::table('inventory_balances')
            ->join('items', 'inventory_balances.item_id', '=', 'items.id')
            ->select('items.name as item_name', 'inventory_balances.quantity', 'items.reorder_level')
            ->whereRaw('inventory_balances.quantity <= COALESCE(items.reorder_level, 0)')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
