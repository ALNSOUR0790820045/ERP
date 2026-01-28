<x-filament-panels::page>
    <div class="space-y-6">
        {{-- اختيار المشروع --}}
        <x-filament::section>
            <div class="max-w-md">
                {{ $this->form }}
            </div>
        </x-filament::section>

        {{-- إحصائيات المشروع --}}
        @php $stats = $this->getProjectStats(); @endphp
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
            <x-filament::section>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_tasks'] }}</div>
                    <div class="text-sm text-gray-500">إجمالي المهام</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-2xl font-bold text-success-600">{{ $stats['completed'] }}</div>
                    <div class="text-sm text-gray-500">مكتملة</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-2xl font-bold text-info-600">{{ $stats['in_progress'] }}</div>
                    <div class="text-sm text-gray-500">قيد التنفيذ</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-600">{{ $stats['not_started'] }}</div>
                    <div class="text-sm text-gray-500">لم تبدأ</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-2xl font-bold text-danger-600">{{ $stats['delayed'] }}</div>
                    <div class="text-sm text-gray-500">متأخرة</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-2xl font-bold text-warning-600">{{ $stats['critical'] }}</div>
                    <div class="text-sm text-gray-500">حرجة</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-2xl font-bold text-primary-600">{{ $stats['overall_progress'] }}%</div>
                    <div class="text-sm text-gray-500">التقدم الكلي</div>
                </div>
            </x-filament::section>
        </div>

        {{-- مخطط Gantt --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-chart-bar class="w-5 h-5" />
                    <span>مخطط Gantt التفاعلي</span>
                </div>
            </x-slot>

            @php $ganttData = $this->getGanttData(); @endphp

            @if(count($ganttData['tasks']) > 0)
                <div id="gantt-container" class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden" style="height: 600px;">
                    {{-- Gantt Chart Placeholder --}}
                    <div class="p-4">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b dark:border-gray-700">
                                    <th class="text-right p-2">المهمة</th>
                                    <th class="text-center p-2">البداية</th>
                                    <th class="text-center p-2">النهاية</th>
                                    <th class="text-center p-2">المدة</th>
                                    <th class="text-center p-2">التقدم</th>
                                    <th class="p-2 w-1/2">الجدول الزمني</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ganttData['tasks'] as $task)
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="p-2 font-medium">{{ $task['text'] }}</td>
                                    <td class="text-center p-2">{{ $task['start_date'] }}</td>
                                    <td class="text-center p-2">{{ $task['end_date'] }}</td>
                                    <td class="text-center p-2">{{ $task['duration'] }} يوم</td>
                                    <td class="text-center p-2">{{ round($task['progress'] * 100) }}%</td>
                                    <td class="p-2">
                                        <div class="relative h-6 bg-gray-200 dark:bg-gray-600 rounded">
                                            <div class="absolute top-0 left-0 h-full rounded transition-all"
                                                 style="width: {{ $task['progress'] * 100 }}%; background-color: {{ $task['color'] }};">
                                            </div>
                                            <div class="absolute inset-0 flex items-center justify-center text-xs font-medium">
                                                @if($task['type'] === 'milestone')
                                                    <x-heroicon-s-flag class="w-4 h-4 text-warning-500" />
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- التبعيات --}}
                @if(count($ganttData['links']) > 0)
                <div class="mt-4">
                    <h4 class="font-medium mb-2">التبعيات ({{ count($ganttData['links']) }})</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($ganttData['links'] as $link)
                        <span class="inline-flex items-center px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">
                            #{{ $link['source'] }} → #{{ $link['target'] }}
                            @if($link['lag'] != 0)
                                <span class="ml-1 text-gray-500">(+{{ $link['lag'] }} أيام)</span>
                            @endif
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
            @else
                <div class="text-center py-12 text-gray-500">
                    <x-heroicon-o-chart-bar class="w-12 h-12 mx-auto mb-4 opacity-50" />
                    <p>لا توجد مهام في هذا المشروع</p>
                    <a href="{{ route('filament.admin.resources.gantt-tasks.create') }}" 
                       class="mt-4 inline-flex items-center text-primary-600 hover:underline">
                        إضافة مهمة جديدة
                    </a>
                </div>
            @endif
        </x-filament::section>

        {{-- دليل الألوان --}}
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">دليل الألوان</x-slot>
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded" style="background: #22c55e;"></div>
                    <span class="text-sm">مكتملة</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded" style="background: #3b82f6;"></div>
                    <span class="text-sm">قيد التنفيذ</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded" style="background: #6b7280;"></div>
                    <span class="text-sm">لم تبدأ</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded" style="background: #f59e0b;"></div>
                    <span class="text-sm">متوقفة</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded" style="background: #ef4444;"></div>
                    <span class="text-sm">حرجة</span>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
