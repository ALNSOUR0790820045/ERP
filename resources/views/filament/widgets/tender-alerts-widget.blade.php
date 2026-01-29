<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-bell-alert class="w-5 h-5 text-red-500"/>
                <span>تنبيهات العطاءات</span>
            </div>
        </x-slot>
        
        @php
            $alerts = $this->getAlerts();
        @endphp
        
        <div class="space-y-2 max-h-48 overflow-y-auto">
            @forelse($alerts as $alert)
                @php
                    $priorityColors = [
                        'urgent' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                        'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                        'medium' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                        'low' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                    ];
                    $color = $priorityColors[$alert->priority ?? 'low'] ?? $priorityColors['low'];
                @endphp
                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-800 text-sm">
                    <span class="truncate flex-1">{{ $alert->message ?? $alert->alert_type ?? 'تنبيه' }}</span>
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $color }} mr-2">{{ $alert->priority ?? 'عادي' }}</span>
                </div>
            @empty
                <div class="text-center py-4 text-gray-500 text-sm">
                    <x-heroicon-o-check-circle class="w-8 h-8 mx-auto mb-1 text-green-500"/>
                    <p>لا توجد تنبيهات</p>
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
