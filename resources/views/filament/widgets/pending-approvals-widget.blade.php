<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-clipboard-document-check class="w-5 h-5 text-blue-500"/>
                <span>الموافقات المعلقة</span>
            </div>
        </x-slot>
        
        @php
            $approvals = $this->getApprovals();
        @endphp
        
        <div class="space-y-2 max-h-48 overflow-y-auto">
            @forelse($approvals as $approval)
                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-800 text-sm">
                    <span class="truncate flex-1">{{ $approval->step_name ?? 'طلب موافقة' }}</span>
                    <span class="px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 mr-2">معلق</span>
                </div>
            @empty
                <div class="text-center py-4 text-gray-500 text-sm">
                    <x-heroicon-o-check-circle class="w-8 h-8 mx-auto mb-1 text-green-500"/>
                    <p>لا توجد موافقات معلقة</p>
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
