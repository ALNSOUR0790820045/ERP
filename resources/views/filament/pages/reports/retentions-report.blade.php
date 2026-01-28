<x-filament-panels::page>
    <form wire:submit="generateReport">
        {{ $this->form }}
        
        <div class="mt-4">
            <x-filament::button type="submit">
                <x-heroicon-o-document-chart-bar class="w-5 h-5 me-2"/>
                إنشاء التقرير
            </x-filament::button>
        </div>
    </form>
    
    @if($showReport)
        <div class="mt-6">
            {{-- ملخص الإجماليات --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                @php $totals = $this->getTotals(); @endphp
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary-600">{{ number_format($totals['total_amount']) }}</div>
                        <div class="text-sm text-gray-500">إجمالي المحتجزات (د.أ)</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-warning-600">{{ number_format($totals['held_amount']) }}</div>
                        <div class="text-sm text-gray-500">المحتجزة (د.أ)</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-info-600">{{ number_format($totals['pending_amount']) }}</div>
                        <div class="text-sm text-gray-500">بانتظار الإفراج (د.أ)</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-success-600">{{ number_format($totals['released_amount']) }}</div>
                        <div class="text-sm text-gray-500">المُفرج عنها (د.أ)</div>
                    </div>
                </x-filament::section>
            </div>
            
            {{-- جدول التفاصيل --}}
            <x-filament::section>
                <x-slot name="heading">
                    تفاصيل المحتجزات ({{ $reportData->count() }} سجل)
                </x-slot>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right">رقم العقد</th>
                                <th class="px-4 py-3 text-right">اسم العقد</th>
                                <th class="px-4 py-3 text-right">نوع المحتجز</th>
                                <th class="px-4 py-3 text-right">رقم المستخلص</th>
                                <th class="px-4 py-3 text-right">المبلغ</th>
                                <th class="px-4 py-3 text-right">النسبة</th>
                                <th class="px-4 py-3 text-right">الحالة</th>
                                <th class="px-4 py-3 text-right">تاريخ الإفراج</th>
                                <th class="px-4 py-3 text-right">الأيام المتبقية</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($reportData as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3">{{ $row['contract_number'] }}</td>
                                    <td class="px-4 py-3">{{ $row['contract_name'] }}</td>
                                    <td class="px-4 py-3">{{ $row['retention_type'] }}</td>
                                    <td class="px-4 py-3">{{ $row['ipc_number'] }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['amount'], 2) }}</td>
                                    <td class="px-4 py-3">{{ $row['percentage'] }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded text-xs
                                            @if($row['status'] === 'محتجزة') bg-warning-100 text-warning-700
                                            @elseif($row['status'] === 'مُفرج عنها') bg-success-100 text-success-700
                                            @elseif($row['status'] === 'مصادرة') bg-danger-100 text-danger-700
                                            @else bg-info-100 text-info-700
                                            @endif">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">{{ $row['release_date'] }}</td>
                                    <td class="px-4 py-3">
                                        @if($row['days_to_release'] !== null)
                                            @if($row['days_to_release'] < 0)
                                                <span class="text-danger-600">متأخر {{ abs($row['days_to_release']) }} يوم</span>
                                            @elseif($row['days_to_release'] <= 30)
                                                <span class="text-warning-600">{{ $row['days_to_release'] }} يوم</span>
                                            @else
                                                <span class="text-success-600">{{ $row['days_to_release'] }} يوم</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                        لا توجد بيانات للعرض
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
