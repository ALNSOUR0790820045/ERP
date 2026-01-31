{{-- 
    شاشة ملخص تدقيق وثائق العطاء
    Tender Validation Summary Modal
--}}
<div class="space-y-4">
    {{-- الحالة العامة --}}
    <div class="p-4 rounded-lg {{ $isReady ? 'bg-success-50 dark:bg-success-950 border border-success-200 dark:border-success-800' : 'bg-danger-50 dark:bg-danger-950 border border-danger-200 dark:border-danger-800' }}">
        <div class="flex items-center gap-3">
            @if($isReady)
                <x-heroicon-o-check-circle class="w-8 h-8 text-success-500" />
                <div>
                    <h3 class="font-bold text-success-700 dark:text-success-300">العطاء جاهز للإرسال للدراسة</h3>
                    <p class="text-sm text-success-600 dark:text-success-400">جميع البيانات الإلزامية مكتملة</p>
                </div>
            @else
                <x-heroicon-o-x-circle class="w-8 h-8 text-danger-500" />
                <div>
                    <h3 class="font-bold text-danger-700 dark:text-danger-300">يوجد بيانات ناقصة</h3>
                    <p class="text-sm text-danger-600 dark:text-danger-400">
                        {{ $totalErrors }} خطأ{{ $totalErrors > 1 ? '' : '' }}
                        @if($totalWarnings > 0)
                            و {{ $totalWarnings }} تحذير
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- تفاصيل التدقيق --}}
    <div class="space-y-3">
        {{-- البيانات الأساسية --}}
        <div class="border rounded-lg overflow-hidden">
            <div class="flex items-center justify-between p-3 {{ empty($summary['basic']) ? 'bg-success-50 dark:bg-success-950' : 'bg-danger-50 dark:bg-danger-950' }}">
                <div class="flex items-center gap-2">
                    @if(empty($summary['basic']))
                        <x-heroicon-o-check-circle class="w-5 h-5 text-success-500" />
                    @else
                        <x-heroicon-o-x-circle class="w-5 h-5 text-danger-500" />
                    @endif
                    <span class="font-semibold">البيانات الأساسية</span>
                </div>
                <span class="text-sm {{ empty($summary['basic']) ? 'text-success-600' : 'text-danger-600' }}">
                    {{ empty($summary['basic']) ? 'مكتمل ✓' : count($summary['basic']) . ' مشكلة' }}
                </span>
            </div>
            @if(!empty($summary['basic']))
                <div class="p-3 bg-white dark:bg-gray-900 text-sm space-y-1">
                    @foreach($summary['basic'] as $error)
                        <p class="text-danger-600 dark:text-danger-400">{{ $error }}</p>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- التواريخ --}}
        <div class="border rounded-lg overflow-hidden">
            @php
                $dateErrors = collect($summary['dates'])->filter(fn ($e) => !str_contains($e, 'تحذير'))->count();
                $dateWarnings = collect($summary['dates'])->filter(fn ($e) => str_contains($e, 'تحذير'))->count();
            @endphp
            <div class="flex items-center justify-between p-3 {{ $dateErrors === 0 && $dateWarnings === 0 ? 'bg-success-50 dark:bg-success-950' : ($dateErrors > 0 ? 'bg-danger-50 dark:bg-danger-950' : 'bg-warning-50 dark:bg-warning-950') }}">
                <div class="flex items-center gap-2">
                    @if($dateErrors === 0 && $dateWarnings === 0)
                        <x-heroicon-o-check-circle class="w-5 h-5 text-success-500" />
                    @elseif($dateErrors > 0)
                        <x-heroicon-o-x-circle class="w-5 h-5 text-danger-500" />
                    @else
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-500" />
                    @endif
                    <span class="font-semibold">التواريخ والمواعيد</span>
                </div>
                <span class="text-sm {{ $dateErrors === 0 && $dateWarnings === 0 ? 'text-success-600' : ($dateErrors > 0 ? 'text-danger-600' : 'text-warning-600') }}">
                    @if($dateErrors === 0 && $dateWarnings === 0)
                        مكتمل ✓
                    @else
                        @if($dateErrors > 0) {{ $dateErrors }} خطأ @endif
                        @if($dateWarnings > 0) {{ $dateWarnings }} تحذير @endif
                    @endif
                </span>
            </div>
            @if(!empty($summary['dates']))
                <div class="p-3 bg-white dark:bg-gray-900 text-sm space-y-1">
                    @foreach($summary['dates'] as $error)
                        @if(str_contains($error, 'تحذير'))
                            <p class="text-warning-600 dark:text-warning-400">{{ $error }}</p>
                        @else
                            <p class="text-danger-600 dark:text-danger-400">{{ $error }}</p>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        {{-- الوثائق --}}
        <div class="border rounded-lg overflow-hidden">
            @php
                $docErrors = collect($summary['documents'])->filter(fn ($e) => !str_contains($e, 'تحذير'))->count();
                $docWarnings = collect($summary['documents'])->filter(fn ($e) => str_contains($e, 'تحذير'))->count();
            @endphp
            <div class="flex items-center justify-between p-3 {{ $docErrors === 0 && $docWarnings === 0 ? 'bg-success-50 dark:bg-success-950' : ($docErrors > 0 ? 'bg-danger-50 dark:bg-danger-950' : 'bg-warning-50 dark:bg-warning-950') }}">
                <div class="flex items-center gap-2">
                    @if($docErrors === 0 && $docWarnings === 0)
                        <x-heroicon-o-check-circle class="w-5 h-5 text-success-500" />
                    @elseif($docErrors > 0)
                        <x-heroicon-o-x-circle class="w-5 h-5 text-danger-500" />
                    @else
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-500" />
                    @endif
                    <span class="font-semibold">الوثائق والمستندات</span>
                </div>
                <span class="text-sm {{ $docErrors === 0 && $docWarnings === 0 ? 'text-success-600' : ($docErrors > 0 ? 'text-danger-600' : 'text-warning-600') }}">
                    @if($docErrors === 0 && $docWarnings === 0)
                        مكتمل ✓
                    @else
                        @if($docErrors > 0) {{ $docErrors }} خطأ @endif
                        @if($docWarnings > 0) {{ $docWarnings }} تحذير @endif
                    @endif
                </span>
            </div>
            @if(!empty($summary['documents']))
                <div class="p-3 bg-white dark:bg-gray-900 text-sm space-y-1">
                    @foreach($summary['documents'] as $error)
                        @if(str_contains($error, 'تحذير'))
                            <p class="text-warning-600 dark:text-warning-400">{{ $error }}</p>
                        @else
                            <p class="text-danger-600 dark:text-danger-400">{{ $error }}</p>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        {{-- الكفالات --}}
        <div class="border rounded-lg overflow-hidden">
            @php
                $bondErrors = collect($summary['bonds'])->filter(fn ($e) => !str_contains($e, 'تحذير'))->count();
                $bondWarnings = collect($summary['bonds'])->filter(fn ($e) => str_contains($e, 'تحذير'))->count();
            @endphp
            <div class="flex items-center justify-between p-3 {{ $bondErrors === 0 && $bondWarnings === 0 ? 'bg-success-50 dark:bg-success-950' : ($bondErrors > 0 ? 'bg-danger-50 dark:bg-danger-950' : 'bg-warning-50 dark:bg-warning-950') }}">
                <div class="flex items-center gap-2">
                    @if($bondErrors === 0 && $bondWarnings === 0)
                        <x-heroicon-o-check-circle class="w-5 h-5 text-success-500" />
                    @elseif($bondErrors > 0)
                        <x-heroicon-o-x-circle class="w-5 h-5 text-danger-500" />
                    @else
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-500" />
                    @endif
                    <span class="font-semibold">الكفالات والضمانات</span>
                </div>
                <span class="text-sm {{ $bondErrors === 0 && $bondWarnings === 0 ? 'text-success-600' : ($bondErrors > 0 ? 'text-danger-600' : 'text-warning-600') }}">
                    @if($bondErrors === 0 && $bondWarnings === 0)
                        مكتمل ✓
                    @else
                        @if($bondErrors > 0) {{ $bondErrors }} خطأ @endif
                        @if($bondWarnings > 0) {{ $bondWarnings }} تحذير @endif
                    @endif
                </span>
            </div>
            @if(!empty($summary['bonds']))
                <div class="p-3 bg-white dark:bg-gray-900 text-sm space-y-1">
                    @foreach($summary['bonds'] as $error)
                        @if(str_contains($error, 'تحذير'))
                            <p class="text-warning-600 dark:text-warning-400">{{ $error }}</p>
                        @else
                            <p class="text-danger-600 dark:text-danger-400">{{ $error }}</p>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ملاحظة --}}
    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm text-gray-600 dark:text-gray-400">
        <strong>ملاحظة:</strong>
        <ul class="list-disc list-inside mt-1 space-y-1">
            <li>الأخطاء (باللون الأحمر) يجب حلها قبل الإرسال للدراسة</li>
            <li>التحذيرات (باللون البرتقالي) اختيارية ولكن يُنصح بمعالجتها</li>
        </ul>
    </div>
</div>
