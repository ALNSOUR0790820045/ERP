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
            {{-- شرائح الضريبة --}}
            @if($taxBrackets->isNotEmpty())
                <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-lg mb-6">
                    <h3 class="font-bold text-amber-800 dark:text-amber-200 mb-2">
                        شرائح ضريبة الدخل - {{ $data['year'] }} 
                        ({{ $data['taxpayer_type'] === 'individual' ? 'أفراد' : 'عائلات' }})
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-6 gap-2 text-sm">
                        @foreach($this->getTaxBracketsSummary() as $bracket)
                            <div class="bg-white dark:bg-gray-800 p-2 rounded text-center">
                                <div class="text-xs text-gray-500">{{ $bracket['min'] }} - {{ $bracket['max'] }}</div>
                                <div class="font-bold text-amber-600">{{ $bracket['rate'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
            {{-- ملخص الإجماليات --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                @php $totals = $this->getTotals(); @endphp
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary-600">{{ $totals['employee_count'] }}</div>
                        <div class="text-sm text-gray-500">عدد الموظفين</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-600">{{ number_format($totals['total_income']) }}</div>
                        <div class="text-sm text-gray-500">إجمالي الدخل السنوي (د.أ)</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-warning-600">{{ number_format($totals['total_taxable']) }}</div>
                        <div class="text-sm text-gray-500">الدخل الخاضع للضريبة (د.أ)</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-danger-600">{{ number_format($totals['total_tax']) }}</div>
                        <div class="text-sm text-gray-500">إجمالي الضريبة المستحقة (د.أ)</div>
                    </div>
                </x-filament::section>
            </div>
            
            {{-- جدول التفاصيل --}}
            <x-filament::section>
                <x-slot name="heading">
                    تفاصيل ضريبة الدخل للموظفين
                </x-slot>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right">رقم الموظف</th>
                                <th class="px-4 py-3 text-right">اسم الموظف</th>
                                <th class="px-4 py-3 text-right">القسم</th>
                                <th class="px-4 py-3 text-right">الرقم الوطني</th>
                                <th class="px-4 py-3 text-right">الدخل السنوي</th>
                                <th class="px-4 py-3 text-right">الدخل الخاضع</th>
                                <th class="px-4 py-3 text-right">الضريبة المستحقة</th>
                                <th class="px-4 py-3 text-right">النسبة الفعلية</th>
                                <th class="px-4 py-3 text-right">الشريحة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($reportData as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3">{{ $row['employee_number'] }}</td>
                                    <td class="px-4 py-3">{{ $row['employee_name'] }}</td>
                                    <td class="px-4 py-3">{{ $row['department'] }}</td>
                                    <td class="px-4 py-3">{{ $row['national_id'] }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['annual_income'], 2) }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['taxable_income'], 2) }}</td>
                                    <td class="px-4 py-3 text-danger-600 font-semibold">{{ number_format($row['tax_amount'], 2) }}</td>
                                    <td class="px-4 py-3">{{ $row['effective_rate'] }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded text-xs
                                            @if($row['tax_bracket'] === 'معفي') bg-success-100 text-success-700
                                            @else bg-warning-100 text-warning-700
                                            @endif">
                                            {{ $row['tax_bracket'] }}
                                        </span>
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
                        <tfoot class="bg-gray-100 dark:bg-gray-800 font-bold">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right">الإجمالي</td>
                                <td class="px-4 py-3">{{ number_format($totals['total_income'], 2) }}</td>
                                <td class="px-4 py-3">{{ number_format($totals['total_taxable'], 2) }}</td>
                                <td class="px-4 py-3 text-danger-600">{{ number_format($totals['total_tax'], 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
