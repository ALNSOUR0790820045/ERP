<x-filament-panels::page>
    <form wire:submit="generateReport">
        {{ $this->form }}
        
        <div class="mt-4 flex gap-3">
            <x-filament::button type="submit">
                <x-heroicon-o-document-chart-bar class="w-5 h-5 me-2"/>
                إنشاء التقرير
            </x-filament::button>
            
            @if($showReport)
                <x-filament::button color="success" wire:click="exportExcel">
                    <x-heroicon-o-arrow-down-tray class="w-5 h-5 me-2"/>
                    تصدير Excel
                </x-filament::button>
                
                <x-filament::button color="danger" wire:click="exportPdf">
                    <x-heroicon-o-document class="w-5 h-5 me-2"/>
                    تصدير PDF
                </x-filament::button>
            @endif
        </div>
    </form>
    
    @if($showReport)
        <div class="mt-6">
            {{-- ملخص الإجماليات --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                @php $totals = $this->getTotals(); @endphp
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary-600">{{ number_format($totals['total_cost']) }}</div>
                        <div class="text-sm text-gray-500">إجمالي تكلفة الاقتناء (د.أ)</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-warning-600">{{ number_format($totals['total_accumulated']) }}</div>
                        <div class="text-sm text-gray-500">الإهلاك المتراكم (د.أ)</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-info-600">{{ number_format($totals['total_period']) }}</div>
                        <div class="text-sm text-gray-500">إهلاك الفترة (د.أ)</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-success-600">{{ number_format($totals['total_book_value']) }}</div>
                        <div class="text-sm text-gray-500">القيمة الدفترية (د.أ)</div>
                    </div>
                </x-filament::section>
            </div>
            
            {{-- جدول التفاصيل --}}
            <x-filament::section>
                <x-slot name="heading">
                    تفاصيل الأصول ({{ $reportData->count() }} أصل)
                </x-slot>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right">كود الأصل</th>
                                <th class="px-4 py-3 text-right">اسم الأصل</th>
                                <th class="px-4 py-3 text-right">التصنيف</th>
                                <th class="px-4 py-3 text-right">تاريخ الاقتناء</th>
                                <th class="px-4 py-3 text-right">تكلفة الاقتناء</th>
                                <th class="px-4 py-3 text-right">طريقة الإهلاك</th>
                                <th class="px-4 py-3 text-right">العمر الإنتاجي</th>
                                <th class="px-4 py-3 text-right">الإهلاك المتراكم</th>
                                <th class="px-4 py-3 text-right">إهلاك الفترة</th>
                                <th class="px-4 py-3 text-right">القيمة الدفترية</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($reportData as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3">{{ $row['asset_code'] }}</td>
                                    <td class="px-4 py-3">{{ $row['asset_name'] }}</td>
                                    <td class="px-4 py-3">{{ $row['category'] }}</td>
                                    <td class="px-4 py-3">{{ $row['acquisition_date'] }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['acquisition_cost'], 2) }}</td>
                                    <td class="px-4 py-3">{{ $row['depreciation_method'] }}</td>
                                    <td class="px-4 py-3">{{ $row['useful_life'] }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['accumulated_depreciation'], 2) }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['period_depreciation'], 2) }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ number_format($row['book_value'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
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
