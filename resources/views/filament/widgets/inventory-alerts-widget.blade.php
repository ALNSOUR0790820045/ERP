<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-500"/>
                <span>تنبيهات المخزون</span>
            </div>
        </x-slot>
        
        @php
            $alerts = $this->getAlerts();
        @endphp
        
        <div class="space-y-2 max-h-48 overflow-y-auto">
            @forelse($alerts as $alert)
                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-800 text-sm">
                    <span class="truncate flex-1">{{ $alert->item_name }}</span>
                    <div class="flex items-center gap-1 mr-2">
                        @if($alert->quantity <= 0)
                            <span class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">نفاد</span>
                        @else
                            <span class="px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">{{ number_format($alert->quantity) }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-gray-500 text-sm">
                    <x-heroicon-o-check-circle class="w-8 h-8 mx-auto mb-1 text-green-500"/>
                    <p>المخزون آمن</p>
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
