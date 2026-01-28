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
                        <div class="text-2xl font-bold text-primary-600">{{ $totals['ipc_count'] }}</div>
                        <div class="text-sm text-gray-500">عدد المستخلصات</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-600">{{ number_format($totals['total_gross']) }}</div>
                        <div class="text-sm text-gray-500">الإجمالي قبل الخصم (د.أ)</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-danger-600">{{ number_format($totals['total_deductions']) }}</div>
                        <div class="text-sm text-gray-500">الحسميات (د.أ)</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-warning-600">{{ number_format($totals['total_retentions']) }}</div>
                        <div class="text-sm text-gray-500">المحتجزات (د.أ)</div>
                    </div>
                </x-filament::section>
                
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-success-600">{{ number_format($totals['total_net']) }}</div>
                        <div class="text-sm text-gray-500">صافي المستحق (د.أ)</div>
                    </div>
                </x-filament::section>
            </div>
            
            {{-- ملخص حسب العقد --}}
            @if($this->getContractSummary()->count() > 1)
                <x-filament::section class="mb-6">
                    <x-slot name="heading">ملخص حسب العقد</x-slot>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($this->getContractSummary() as $contract)
                            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                <div class="font-bold">{{ $contract['contract_number'] }}</div>
                                <div class="text-sm text-gray-500">{{ $contract['contract_name'] }}</div>
                                <div class="flex justify-between mt-2">
                                    <span>عدد المستخلصات: {{ $contract['ipc_count'] }}</span>
                                    <span class="font-semibold text-success-600">{{ number_format($contract['total_amount']) }} د.أ</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif
            
            {{-- جدول التفاصيل --}}
            <x-filament::section>
                <x-slot name="heading">
                    تفاصيل المستخلصات
                </x-slot>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right">رقم المستخلص</th>
                                <th class="px-4 py-3 text-right">رقم العقد</th>
                                <th class="px-4 py-3 text-right">اسم العقد</th>
                                <th class="px-4 py-3 text-right">تاريخ المستخلص</th>
                                <th class="px-4 py-3 text-right">فترة العمل</th>
                                <th class="px-4 py-3 text-right">الإجمالي</th>
                                <th class="px-4 py-3 text-right">الحسميات</th>
                                <th class="px-4 py-3 text-right">المحتجزات</th>
                                <th class="px-4 py-3 text-right">الصافي</th>
                                <th class="px-4 py-3 text-right">الحالة</th>
                                <th class="px-4 py-3 text-right">نسبة الإنجاز</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($reportData as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3 font-medium">{{ $row['ipc_number'] }}</td>
                                    <td class="px-4 py-3">{{ $row['contract_number'] }}</td>
                                    <td class="px-4 py-3">{{ $row['contract_name'] }}</td>
                                    <td class="px-4 py-3">{{ $row['ipc_date'] }}</td>
                                    <td class="px-4 py-3 text-xs">
                                        {{ $row['period_from'] }} - {{ $row['period_to'] }}
                                    </td>
                                    <td class="px-4 py-3">{{ number_format($row['gross_amount'], 2) }}</td>
                                    <td class="px-4 py-3 text-danger-600">{{ number_format($row['deductions'], 2) }}</td>
                                    <td class="px-4 py-3 text-warning-600">{{ number_format($row['retentions'], 2) }}</td>
                                    <td class="px-4 py-3 font-semibold text-success-600">{{ number_format($row['net_amount'], 2) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded text-xs
                                            @if($row['status'] === 'معتمد' || $row['status'] === 'مدفوع') bg-success-100 text-success-700
                                            @elseif($row['status'] === 'مرفوض') bg-danger-100 text-danger-700
                                            @elseif($row['status'] === 'مسودة') bg-gray-100 text-gray-700
                                            @else bg-info-100 text-info-700
                                            @endif">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-16 bg-gray-200 rounded-full h-2">
                                                <div class="bg-primary-600 h-2 rounded-full" style="width: {{ $row['completion_percentage'] }}%"></div>
                                            </div>
                                            <span class="text-xs">{{ $row['completion_percentage'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-4 py-8 text-center text-gray-500">
                                        لا توجد بيانات للعرض
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-100 dark:bg-gray-800 font-bold">
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-right">الإجمالي</td>
                                <td class="px-4 py-3">{{ number_format($totals['total_gross'], 2) }}</td>
                                <td class="px-4 py-3 text-danger-600">{{ number_format($totals['total_deductions'], 2) }}</td>
                                <td class="px-4 py-3 text-warning-600">{{ number_format($totals['total_retentions'], 2) }}</td>
                                <td class="px-4 py-3 text-success-600">{{ number_format($totals['total_net'], 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
