<?php

namespace App\Filament\Widgets;

use App\Models\NotificationLog;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class NotificationsWidget extends Widget
{
    protected static string $view = 'filament.widgets.notifications-widget';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public function getNotifications(): Collection
    {
        return NotificationLog::forUser(auth()->id())
            ->byChannel('database')
            ->recent(7)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }

    public function getUnreadCount(): int
    {
        return NotificationLog::getUnreadCount(auth()->id());
    }

    public function markAsRead(int $id): void
    {
        $notification = NotificationLog::find($id);
        if ($notification && $notification->user_id === auth()->id()) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        NotificationLog::markAllAsRead(auth()->id());
    }
}
