@php
    use App\Enums\TenderStatus;
    
    $stages = [
        ['key' => 'discovery', 'label' => 'الرصد والتسجيل', 'icon' => 'magnifying-glass', 'statuses' => [TenderStatus::NEW]],
        ['key' => 'study', 'label' => 'الدراسة والقرار', 'icon' => 'clipboard-document-check', 'statuses' => [TenderStatus::STUDYING, TenderStatus::GO, TenderStatus::NO_GO]],
        ['key' => 'preparation', 'label' => 'إعداد العرض', 'icon' => 'document-text', 'statuses' => [TenderStatus::PRICING, TenderStatus::READY]],
        ['key' => 'submission', 'label' => 'التقديم', 'icon' => 'paper-airplane', 'statuses' => [TenderStatus::SUBMITTED]],
        ['key' => 'opening', 'label' => 'الفتح والنتائج', 'icon' => 'chart-bar', 'statuses' => [TenderStatus::OPENING]],
        ['key' => 'award', 'label' => 'الترسية والتحويل', 'icon' => 'trophy', 'statuses' => [TenderStatus::WON, TenderStatus::LOST]],
    ];
    
    $currentStatus = $getRecord()->status;
    $currentIndex = 0;
    
    foreach ($stages as $index => $stage) {
        if (in_array($currentStatus, $stage['statuses'])) {
            $currentIndex = $index;
            break;
        }
    }
    
    // التحقق من الحالات الخاصة
    $isDeclined = $currentStatus === TenderStatus::NO_GO;
    $isCancelled = $currentStatus === TenderStatus::CANCELLED;
@endphp

<div class="w-full py-4">
    @if($isDeclined || $isCancelled)
        <div class="flex items-center justify-center p-4 bg-red-100 dark:bg-red-900/20 rounded-lg">
            <x-heroicon-o-x-circle class="w-8 h-8 text-red-500 ml-3" />
            <span class="text-red-700 dark:text-red-300 font-bold text-lg">
                {{ $isDeclined ? 'تم رفض المشاركة (No-Go)' : 'تم إلغاء العطاء' }}
            </span>
        </div>
    @else
        <div class="flex items-center justify-between">
            @foreach($stages as $index => $stage)
                @php
                    $isPassed = $index < $currentIndex;
                    $isCurrent = $index === $currentIndex;
                    $isFuture = $index > $currentIndex;
                @endphp
                
                <div class="flex flex-col items-center flex-1 {{ $index < count($stages) - 1 ? 'relative' : '' }}">
                    {{-- الدائرة والأيقونة --}}
                    <div class="flex items-center justify-center w-12 h-12 rounded-full 
                        {{ $isPassed ? 'bg-success-500' : ($isCurrent ? 'bg-primary-500 ring-4 ring-primary-200 dark:ring-primary-800' : 'bg-gray-200 dark:bg-gray-700') }} 
                        transition-all duration-300">
                        @if($isPassed)
                            <x-heroicon-s-check class="w-6 h-6 text-white" />
                        @else
                            <x-dynamic-component 
                                :component="'heroicon-o-' . $stage['icon']" 
                                class="w-6 h-6 {{ $isCurrent ? 'text-white' : 'text-gray-400 dark:text-gray-500' }}" 
                            />
                        @endif
                    </div>
                    
                    {{-- العنوان --}}
                    <span class="mt-2 text-xs font-medium text-center 
                        {{ $isPassed ? 'text-success-600 dark:text-success-400' : ($isCurrent ? 'text-primary-600 dark:text-primary-400 font-bold' : 'text-gray-400 dark:text-gray-500') }}">
                        {{ $stage['label'] }}
                    </span>
                    
                    {{-- الخط الواصل --}}
                    @if($index < count($stages) - 1)
                        <div class="absolute top-6 right-1/2 w-full h-0.5 
                            {{ $isPassed ? 'bg-success-500' : 'bg-gray-200 dark:bg-gray-700' }} 
                            -translate-y-1/2 z-0" 
                            style="right: calc(50% + 1.5rem); width: calc(100% - 3rem);">
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        
        {{-- معلومات إضافية --}}
        <div class="flex justify-center mt-4">
            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium
                {{ $currentStatus->getColor() === 'success' ? 'bg-success-100 text-success-700 dark:bg-success-900/20 dark:text-success-300' : '' }}
                {{ $currentStatus->getColor() === 'warning' ? 'bg-warning-100 text-warning-700 dark:bg-warning-900/20 dark:text-warning-300' : '' }}
                {{ $currentStatus->getColor() === 'danger' ? 'bg-danger-100 text-danger-700 dark:bg-danger-900/20 dark:text-danger-300' : '' }}
                {{ $currentStatus->getColor() === 'info' ? 'bg-info-100 text-info-700 dark:bg-info-900/20 dark:text-info-300' : '' }}
                {{ $currentStatus->getColor() === 'primary' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300' : '' }}
                {{ !in_array($currentStatus->getColor(), ['success', 'warning', 'danger', 'info', 'primary']) ? 'bg-gray-100 text-gray-700 dark:bg-gray-900/20 dark:text-gray-300' : '' }}
            ">
                {{ $currentStatus->getLabel() }}
            </span>
        </div>
    @endif
</div>
