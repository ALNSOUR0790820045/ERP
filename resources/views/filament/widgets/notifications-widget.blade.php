<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-bell class="w-5 h-5" />
                    <span>الإشعارات</span>
                    @if($this->getUnreadCount() > 0)
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-danger-500 rounded-full">
                            {{ $this->getUnreadCount() }}
                        </span>
                    @endif
                </div>
                @if($this->getUnreadCount() > 0)
                    <button wire:click="markAllAsRead" class="text-sm text-primary-600 hover:underline">
                        تعليم الكل كمقروء
                    </button>
                @endif
            </div>
        </x-slot>

        @php $notifications = $this->getNotifications(); @endphp

        @if($notifications->isEmpty())
            <div class="text-center py-6 text-gray-500">
                <x-heroicon-o-bell-slash class="w-8 h-8 mx-auto mb-2 opacity-50" />
                <p>لا توجد إشعارات</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($notifications as $notification)
                    <div class="flex items-start gap-3 p-3 rounded-lg transition {{ $notification->is_read ? 'bg-gray-50 dark:bg-gray-800' : 'bg-primary-50 dark:bg-primary-900/20' }}">
                        <div class="flex-shrink-0 mt-1">
                            @if(!$notification->is_read)
                                <span class="inline-block w-2 h-2 bg-primary-500 rounded-full"></span>
                            @else
                                <span class="inline-block w-2 h-2 bg-gray-300 rounded-full"></span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $notification->title }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($notification->body, 100) }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        @if(!$notification->is_read)
                            <button wire:click="markAsRead({{ $notification->id }})" 
                                    class="text-gray-400 hover:text-gray-600" 
                                    title="تعليم كمقروء">
                                <x-heroicon-o-check class="w-4 h-4" />
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-4 pt-4 border-t dark:border-gray-700 text-center">
                <a href="{{ route('filament.admin.resources.notification-templates.index') }}" 
                   class="text-sm text-primary-600 hover:underline">
                    إدارة قوالب الإشعارات
                </a>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
