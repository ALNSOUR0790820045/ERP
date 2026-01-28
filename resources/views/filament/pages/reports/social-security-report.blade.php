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
            {{-- معلومات الإعدادات --}}
            @php $settings = $this->getSettings(); @endphp
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg mb-6">
                <h3 class="font-bold text-blue-800 dark:text-blue-200 mb-2">إعدادات الضمان الاجتماعي - {{ $data['year'] }}</h3>
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">نسبة صاحب العمل:</span>
                        <span class="font-semibold text-blue-700 dark:text-blue-300">{{ $settings['employer_rate'] }}%</span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">نسبة الموظف:</span>
                        <span class="font-semibold text-blue-700 dark:text-blue-300">{{ $settings['employee_rate'] }}%</span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">الحد الأدنى للأجور:</span>
                        <span class="font-semibold text-blue-700 dark:text-blue-300">{{ number_format($settings['minimum_wage']) }} د.أ</span>
                    </div>
                </div>
            </div>
            
            {{-- ملخص الإجماليات --}}
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                @php $totals = $this->getTotals(); @endphp
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary-600">{{ $totals['employee_count'] }}</div>
                        <div class="text-sm text-gray-500">عدد الموظفين</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-600">{{ number_format($totals['total_gross']) }}</div>
                        <div class="text-sm text-gray-500">إجمالي الرواتب (د.أ)</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-info-600">{{ number_format($totals['total_employee']) }}</div>
                        <div class="text-sm text-gray-500">حصة الموظفين (د.أ)</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-warning-600">{{ number_format($totals['total_employer']) }}</div>
                        <div class="text-sm text-gray-500">حصة صاحب العمل (د.أ)</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-success-600">{{ number_format($totals['total_combined']) }}</div>
                        <div class="text-sm text-gray-500">إجمالي الاشتراكات (د.أ)</div>
                    </div>
                </x-filament::section>
            </div>
            
            {{-- جدول التفاصيل --}}
            <x-filament::section>
                <x-slot name="heading">
                    تفاصيل اشتراكات الموظفين
                </x-slot>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right">رقم الموظف</th>
                                <th class="px-4 py-3 text-right">اسم الموظف</th>
                                <th class="px-4 py-3 text-right">القسم</th>
                                <th class="px-4 py-3 text-right">الرقم الوطني</th>
                                <th class="px-4 py-3 text-right">الراتب الإجمالي</th>
                                <th class="px-4 py-3 text-right">حصة الموظف</th>
                                <th class="px-4 py-3 text-right">حصة صاحب العمل</th>
                                <th class="px-4 py-3 text-right">إجمالي الاشتراك</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($reportData as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3">{{ $row['employee_number'] }}</td>
                                    <td class="px-4 py-3">{{ $row['employee_name'] }}</td>
                                    <td class="px-4 py-3">{{ $row['department'] }}</td>
                                    <td class="px-4 py-3">{{ $row['national_id'] }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['gross_salary'], 2) }}</td>
                                    <td class="px-4 py-3 text-info-600">{{ number_format($row['employee_contribution'], 2) }}</td>
                                    <td class="px-4 py-3 text-warning-600">{{ number_format($row['employer_contribution'], 2) }}</td>
                                    <td class="px-4 py-3 font-semibold text-success-600">{{ number_format($row['total_contribution'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                        لا توجد بيانات للعرض
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-100 dark:bg-gray-800 font-bold">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right">الإجمالي</td>
                                <td class="px-4 py-3">{{ number_format($totals['total_gross'], 2) }}</td>
                                <td class="px-4 py-3 text-info-600">{{ number_format($totals['total_employee'], 2) }}</td>
                                <td class="px-4 py-3 text-warning-600">{{ number_format($totals['total_employer'], 2) }}</td>
                                <td class="px-4 py-3 text-success-600">{{ number_format($totals['total_combined'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
