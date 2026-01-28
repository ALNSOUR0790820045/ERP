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
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                @php $totals = $this->getTotals(); @endphp
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary-600">{{ $totals['total_items'] }}</div>
                        <div class="text-sm text-gray-500">عدد الأصناف</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-600">{{ number_format($totals['total_quantity']) }}</div>
                        <div class="text-sm text-gray-500">إجمالي الكميات</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-success-600">{{ number_format($totals['total_value']) }}</div>
                        <div class="text-sm text-gray-500">إجمالي القيمة (د.أ)</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-warning-600">{{ $totals['low_stock_count'] }}</div>
                        <div class="text-sm text-gray-500">مخزون منخفض</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-danger-600">{{ $totals['out_of_stock_count'] }}</div>
                        <div class="text-sm text-gray-500">نفاد المخزون</div>
                    </div>
                </x-filament::section>
            </div>
            
            {{-- ملخص حسب التصنيف --}}
            @if($this->getCategorySummary()->count() > 1)
                <x-filament::section class="mb-6">
                    <x-slot name="heading">ملخص حسب التصنيف</x-slot>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        @foreach($this->getCategorySummary()->take(8) as $category)
                            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                <div class="font-bold">{{ $category['category'] }}</div>
                                <div class="flex justify-between mt-2">
                                    <span class="text-sm text-gray-500">{{ $category['item_count'] }} صنف</span>
                                    <span class="font-semibold text-success-600">{{ number_format($category['total_value']) }} د.أ</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif
            
            {{-- جدول التفاصيل --}}
            <x-filament::section>
                <x-slot name="heading">
                    تفاصيل المخزون
                </x-slot>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right">كود المادة</th>
                                <th class="px-4 py-3 text-right">اسم المادة</th>
                                <th class="px-4 py-3 text-right">التصنيف</th>
                                <th class="px-4 py-3 text-right">المستودع</th>
                                <th class="px-4 py-3 text-right">الوحدة</th>
                                <th class="px-4 py-3 text-right">الكمية</th>
                                <th class="px-4 py-3 text-right">سعر الوحدة</th>
                                <th class="px-4 py-3 text-right">إجمالي القيمة</th>
                                <th class="px-4 py-3 text-right">حد إعادة الطلب</th>
                                <th class="px-4 py-3 text-right">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($reportData as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3 font-medium">{{ $row['material_code'] }}</td>
                                    <td class="px-4 py-3">{{ $row['material_name'] }}</td>
                                    <td class="px-4 py-3">{{ $row['category'] }}</td>
                                    <td class="px-4 py-3">{{ $row['warehouse'] }}</td>
                                    <td class="px-4 py-3">{{ $row['unit'] }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['quantity'], 2) }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['unit_cost'], 2) }}</td>
                                    <td class="px-4 py-3 font-semibold text-success-600">{{ number_format($row['total_value'], 2) }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['reorder_level']) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded text-xs
                                            @if($row['stock_status'] === 'متوفر') bg-success-100 text-success-700
                                            @elseif($row['stock_status'] === 'منخفض') bg-warning-100 text-warning-700
                                            @else bg-danger-100 text-danger-700
                                            @endif">
                                            {{ $row['stock_status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                        لا توجد بيانات للعرض
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-100 dark:bg-gray-800 font-bold">
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-right">الإجمالي</td>
                                <td class="px-4 py-3">{{ number_format($totals['total_quantity'], 2) }}</td>
                                <td class="px-4 py-3">-</td>
                                <td class="px-4 py-3 text-success-600">{{ number_format($totals['total_value'], 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
