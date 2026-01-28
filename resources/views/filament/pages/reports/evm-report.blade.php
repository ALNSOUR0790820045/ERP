<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            فلترة التقرير
        </x-slot>
        
        <form wire:submit.prevent="$refresh">
            {{ $this->form }}
        </form>
    </x-filament::section>

    @php
        $summary = $this->getSummary();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-primary-600">
                    {{ number_format($summary['avg_spi'], 2) }}
                </div>
                <div class="text-sm text-gray-500">متوسط SPI</div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-primary-600">
                    {{ number_format($summary['avg_cpi'], 2) }}
                </div>
                <div class="text-sm text-gray-500">متوسط CPI</div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold {{ $summary['behind_schedule'] > 0 ? 'text-warning-600' : 'text-success-600' }}">
                    {{ $summary['behind_schedule'] }}
                </div>
                <div class="text-sm text-gray-500">متأخرة عن الجدول</div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold {{ $summary['critical'] > 0 ? 'text-danger-600' : 'text-success-600' }}">
                    {{ $summary['critical'] }}
                </div>
                <div class="text-sm text-gray-500">مشاريع حرجة</div>
            </div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">
            قياسات القيمة المكتسبة
        </x-slot>
        <x-slot name="description">
            <div class="text-sm text-gray-500">
                <span class="font-medium">PV:</span> القيمة المخططة |
                <span class="font-medium">EV:</span> القيمة المكتسبة |
                <span class="font-medium">AC:</span> التكلفة الفعلية |
                <span class="font-medium">SV:</span> فرق الجدول |
                <span class="font-medium">CV:</span> فرق التكلفة |
                <span class="font-medium">SPI:</span> مؤشر الجدول |
                <span class="font-medium">CPI:</span> مؤشر التكلفة
            </div>
        </x-slot>

        {{ $this->table }}
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            ملخص مالي
        </x-slot>

        <div class="grid grid-cols-3 gap-4">
            <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">
                    {{ number_format($summary['total_pv'], 0) }} JOD
                </div>
                <div class="text-sm text-gray-500">إجمالي القيمة المخططة</div>
            </div>

            <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <div class="text-2xl font-bold text-green-600">
                    {{ number_format($summary['total_ev'], 0) }} JOD
                </div>
                <div class="text-sm text-gray-500">إجمالي القيمة المكتسبة</div>
            </div>

            <div class="text-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                <div class="text-2xl font-bold text-orange-600">
                    {{ number_format($summary['total_ac'], 0) }} JOD
                </div>
                <div class="text-sm text-gray-500">إجمالي التكلفة الفعلية</div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
