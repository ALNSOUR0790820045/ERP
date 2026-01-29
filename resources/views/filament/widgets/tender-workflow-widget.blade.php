<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-arrow-path class="w-5 h-5 text-primary-500" />
                <span>مراحل العطاء ({{ $this->getCurrentStage() }}/17)</span>
            </div>
        </x-slot>

        <div class="relative">
            {{-- Progress Bar --}}
            <div class="w-full bg-gray-200 rounded-full h-2.5 mb-6 dark:bg-gray-700">
                <div class="bg-primary-600 h-2.5 rounded-full transition-all duration-500" 
                     style="width: {{ ($this->getCurrentStage() / 17) * 100 }}%">
                </div>
            </div>

            {{-- Stages Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($this::$stages as $number => $stage)
                    @php
                        $status = $this->getStageStatus($number);
                        $colorClass = match($status) {
                            'completed' => 'bg-success-100 border-success-500 text-success-700 dark:bg-success-900 dark:text-success-300',
                            'current' => 'bg-primary-100 border-primary-500 text-primary-700 dark:bg-primary-900 dark:text-primary-300 ring-2 ring-primary-500',
                            'stopped' => 'bg-danger-100 border-danger-500 text-danger-700 dark:bg-danger-900 dark:text-danger-300',
                            default => 'bg-gray-50 border-gray-300 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                        };
                        $iconClass = match($status) {
                            'completed' => 'text-success-500',
                            'current' => 'text-primary-500',
                            'stopped' => 'text-danger-500',
                            default => 'text-gray-400',
                        };
                    @endphp
                    
                    <div class="flex flex-col items-center p-3 rounded-lg border-2 {{ $colorClass }} transition-all hover:scale-105">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full mb-2 {{ $status === 'completed' ? 'bg-success-500' : ($status === 'current' ? 'bg-primary-500' : 'bg-gray-300') }}">
                            @if($status === 'completed')
                                <x-heroicon-s-check class="w-6 h-6 text-white" />
                            @elseif($status === 'current')
                                <span class="text-white font-bold">{{ $number }}</span>
                            @else
                                <span class="text-gray-600 font-medium">{{ $number }}</span>
                            @endif
                        </div>
                        <span class="text-xs text-center font-medium leading-tight">
                            {{ $stage['name'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
