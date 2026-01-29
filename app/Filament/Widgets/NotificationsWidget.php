<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class NotificationsWidget extends Widget
{
    protected static bool $isLazy = true;
    
    protected static string $view = 'filament.widgets.notifications-widget';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public function getNotifications(): Collection
    {
        if (!Schema::hasTable('notification_logs')) {
            return collect();
        }
        
        return DB::table('notification_logs')
            ->where('user_id', auth()->id())
            ->where('channel', 'database')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }

    public function getUnreadCount(): int
    {
        if (!Schema::hasTable('notification_logs')) {
            return 0;
        }
        
        return DB::table('notification_logs')
            ->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();
    }

    public function markAsRead(int $id): void
    {
        if (!Schema::hasTable('notification_logs')) {
            return;
        }
        
        DB::table('notification_logs')
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->update(['read_at' => now()]);
    }

    public function markAllAsRead(): void
    {
        if (!Schema::hasTable('notification_logs')) {
            return;
        }
        
        DB::table('notification_logs')
            ->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
