<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-clock class="w-5 h-5 text-primary-500" />
                <span>الجدول الزمني للعطاء</span>
            </div>
        </x-slot>

        <div class="relative">
            @php $timeline = $this->getTimeline(); @endphp
            
            @if(count($timeline) > 0)
                <div class="relative pr-8">
                    {{-- الخط العمودي --}}
                    <div class="absolute top-2 right-3 bottom-2 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                    
                    <div class="space-y-6">
                        @foreach($timeline as $index => $item)
                            @php
                                $isPast = strtotime($item['date']) < time();
                                $isToday = date('Y-m-d', strtotime($item['date'])) === date('Y-m-d');
                            @endphp
                            
                            <div class="relative flex items-start gap-4 {{ $isPast ? 'opacity-100' : 'opacity-60' }}">
                                {{-- الدائرة --}}
                                <div class="absolute right-0 flex items-center justify-center w-6 h-6 rounded-full 
                                    {{ $isToday ? 'ring-4 ring-' . $item['color'] . '-200 dark:ring-' . $item['color'] . '-800' : '' }}
                                    bg-{{ $item['color'] }}-500 z-10">
                                    <x-dynamic-component 
                                        :component="$item['icon']" 
                                        class="w-3.5 h-3.5 text-white" 
                                    />
                                </div>
                                
                                {{-- المحتوى --}}
                                <div class="flex-1 mr-4 pb-4">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $item['title'] }}
                                        </h4>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($item['date'])->format('Y/m/d') }}
                                            @if($isToday)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200 mr-2">
                                                    اليوم
                                                </span>
                                            @endif
                                        </span>
                                    </div>
                                    @if($item['description'])
                                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                            {{ $item['description'] }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-calendar class="w-12 h-12 mx-auto mb-2 opacity-50" />
                    <p>لا توجد أحداث مسجلة</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
