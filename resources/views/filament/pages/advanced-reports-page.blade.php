<x-filament-panels::page>
    <div class="space-y-6">
        {{-- فلاتر التقرير --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-funnel class="w-5 h-5" />
                    <span>خيارات التقرير</span>
                </div>
            </x-slot>
            {{ $this->form }}
        </x-filament::section>

        {{-- محتوى التقرير --}}
        @php $reportData = $this->getReportData(); @endphp

        @if(isset($reportData['message']))
            <x-filament::section>
                <div class="text-center py-8 text-gray-500">
                    <x-heroicon-o-information-circle class="w-12 h-12 mx-auto mb-4" />
                    <p>{{ $reportData['message'] }}</p>
                </div>
            </x-filament::section>
        @else
            {{-- عنوان التقرير --}}
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ $reportData['title'] ?? 'التقرير' }}
                </h2>
                @if(isset($reportData['period']))
                    <span class="text-sm text-gray-500">{{ $reportData['period'] }}</span>
                @endif
            </div>

            {{-- الإحصائيات (للملخص العام) --}}
            @if(isset($reportData['stats']))
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($reportData['stats'] as $stat)
                        <x-filament::section>
                            <div class="flex items-center gap-4">
                                <div class="p-3 rounded-lg bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900/20">
                                    <x-dynamic-component :component="$stat['icon']" class="w-6 h-6 text-{{ $stat['color'] }}-600" />
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stat['value']) }}</div>
                                    <div class="text-sm text-gray-500">{{ $stat['label'] }}</div>
                                </div>
                            </div>
                        </x-filament::section>
                    @endforeach
                </div>
            @endif

            {{-- الإجماليات (للتقارير الأخرى) --}}
            @if(isset($reportData['totals']))
                <x-filament::section>
                    <x-slot name="heading">الإجماليات</x-slot>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($reportData['totals'] as $key => $value)
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="text-lg font-bold text-gray-900 dark:text-white">
                                    @if(is_numeric($value) && $value > 1000)
                                        {{ number_format($value, 2) }}
                                    @else
                                        {{ $value }}
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500">{{ str_replace('_', ' ', $key) }}</div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif

            {{-- الإيرادات (للتقرير المالي) --}}
            @if(isset($reportData['revenue']))
                <x-filament::section>
                    <x-slot name="heading">الإيرادات</x-slot>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-success-50 dark:bg-success-900/20 rounded-lg">
                            <div class="text-lg font-bold text-success-600">{{ number_format($reportData['revenue']['total_paid'], 2) }}</div>
                            <div class="text-sm text-gray-500">المحصل</div>
                        </div>
                        <div class="text-center p-4 bg-warning-50 dark:bg-warning-900/20 rounded-lg">
                            <div class="text-lg font-bold text-warning-600">{{ number_format($reportData['revenue']['total_pending'], 2) }}</div>
                            <div class="text-sm text-gray-500">قيد الانتظار</div>
                        </div>
                        <div class="text-center p-4 bg-danger-50 dark:bg-danger-900/20 rounded-lg">
                            <div class="text-lg font-bold text-danger-600">{{ number_format($reportData['revenue']['total_overdue'], 2) }}</div>
                            <div class="text-sm text-gray-500">متأخر</div>
                        </div>
                        <div class="text-center p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
                            <div class="text-lg font-bold text-primary-600">{{ number_format($reportData['revenue']['total_invoiced'], 2) }}</div>
                            <div class="text-sm text-gray-500">الإجمالي</div>
                        </div>
                    </div>
                </x-filament::section>
            @endif

            {{-- المقارنة --}}
            @if(isset($reportData['comparison']))
                <x-filament::section>
                    <x-slot name="heading">المقارنة</x-slot>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($reportData['comparison'] as $metric => $data)
                            <div class="p-4 border rounded-lg dark:border-gray-700">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-4 capitalize">{{ $metric }}</h4>
                                <div class="grid grid-cols-3 gap-4 text-center">
                                    <div>
                                        <div class="text-sm text-gray-500">الفترة السابقة</div>
                                        <div class="text-lg font-bold">{{ number_format($data['previous'], is_float($data['previous']) ? 2 : 0) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">الفترة الحالية</div>
                                        <div class="text-lg font-bold">{{ number_format($data['current'], is_float($data['current']) ? 2 : 0) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">التغيير</div>
                                        <div class="text-lg font-bold {{ $data['change'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                            {{ $data['change'] >= 0 ? '+' : '' }}{{ $data['change'] }}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif

            {{-- جدول البيانات --}}
            @if(isset($reportData['data']) && count($reportData['data']) > 0)
                <x-filament::section>
                    <x-slot name="heading">البيانات التفصيلية</x-slot>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                    @foreach(array_keys($reportData['data'][0]) as $header)
                                        <th class="text-right p-3 font-medium">{{ str_replace('_', ' ', $header) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportData['data'] as $row)
                                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        @foreach($row as $value)
                                            <td class="p-3">
                                                @if(is_numeric($value) && $value > 1000)
                                                    {{ number_format($value, 2) }}
                                                @elseif($value instanceof \Carbon\Carbon)
                                                    {{ $value->format('Y-m-d') }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            {{-- حسب القسم (للموظفين) --}}
            @if(isset($reportData['by_department']) && count($reportData['by_department']) > 0)
                <x-filament::section>
                    <x-slot name="heading">حسب القسم</x-slot>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($reportData['by_department'] as $dept => $count)
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $count }}</div>
                                <div class="text-sm text-gray-500">{{ $dept ?: 'بدون قسم' }}</div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif
        @endif
    </div>
</x-filament-panels::page>
