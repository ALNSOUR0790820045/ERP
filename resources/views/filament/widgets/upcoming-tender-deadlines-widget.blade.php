<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-clock class="w-5 h-5 text-orange-500"/>
                <span>مواعيد الإغلاق القريبة</span>
            </div>
        </x-slot>
        
        @php
            $deadlines = $this->getDeadlines();
        @endphp
        
        <div class="space-y-2 max-h-48 overflow-y-auto">
            @forelse($deadlines as $deadline)
                @php
                    $daysRemaining = now()->diffInDays(\Carbon\Carbon::parse($deadline->submission_deadline), false);
                    $dayColor = $daysRemaining <= 3 ? 'text-red-600' : ($daysRemaining <= 7 ? 'text-yellow-600' : 'text-green-600');
                @endphp
                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-800 text-sm">
                    <span class="truncate flex-1">{{ $deadline->name_ar ?? $deadline->name ?? 'غير محدد' }}</span>
                    <span class="{{ $dayColor }} font-bold text-xs whitespace-nowrap mr-2">{{ $daysRemaining }} يوم</span>
                </div>
            @empty
                <div class="text-center py-4 text-gray-500 text-sm">
                    <x-heroicon-o-check-circle class="w-8 h-8 mx-auto mb-1 text-green-500"/>
                    <p>لا توجد مواعيد قريبة</p>
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
