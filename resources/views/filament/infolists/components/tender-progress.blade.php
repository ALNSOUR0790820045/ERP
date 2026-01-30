@php
    use App\Enums\TenderStatus;
    
    $stages = [
        [
            'key' => 'discovery', 
            'label' => 'الرصد', 
            'icon' => 'magnifying-glass', 
            'statuses' => [TenderStatus::NEW],
            'description' => 'رصد وتسجيل العطاء'
        ],
        [
            'key' => 'study', 
            'label' => 'الدراسة', 
            'icon' => 'clipboard-document-check', 
            'statuses' => [TenderStatus::STUDYING, TenderStatus::GO, TenderStatus::NO_GO],
            'description' => 'دراسة وتقييم العطاء'
        ],
        [
            'key' => 'preparation', 
            'label' => 'الإعداد', 
            'icon' => 'document-text', 
            'statuses' => [TenderStatus::PRICING, TenderStatus::READY],
            'description' => 'إعداد العرض الفني والمالي'
        ],
        [
            'key' => 'submission', 
            'label' => 'التقديم', 
            'icon' => 'paper-airplane', 
            'statuses' => [TenderStatus::SUBMITTED],
            'description' => 'تقديم العطاء'
        ],
        [
            'key' => 'opening', 
            'label' => 'الفتح', 
            'icon' => 'envelope-open', 
            'statuses' => [TenderStatus::OPENING],
            'description' => 'فتح المظاريف والنتائج'
        ],
        [
            'key' => 'award', 
            'label' => 'الترسية', 
            'icon' => 'trophy', 
            'statuses' => [TenderStatus::WON, TenderStatus::LOST],
            'description' => 'الترسية والتحويل'
        ],
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
    $isWon = $currentStatus === TenderStatus::WON;
    $isLost = $currentStatus === TenderStatus::LOST;
    
    // حساب نسبة الإنجاز
    $progressPercentage = min(100, round((($currentIndex + 1) / count($stages)) * 100));
@endphp

<div class="w-full py-4 px-2">
    {{-- حالات خاصة: الرفض أو الإلغاء --}}
    @if($isDeclined || $isCancelled)
        <div class="flex items-center justify-center p-6 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800">
            <x-heroicon-o-x-circle class="w-10 h-10 text-red-500 ml-4" />
            <div>
                <span class="text-red-700 dark:text-red-300 font-bold text-lg block">
                    {{ $isDeclined ? '❌ تم رفض المشاركة (No-Go)' : '🚫 تم إلغاء العطاء' }}
                </span>
                <span class="text-red-500 dark:text-red-400 text-sm">
                    {{ $getRecord()->decision_notes ?? $getRecord()->notes ?? '' }}
                </span>
            </div>
        </div>
    
    {{-- حالة الفوز --}}
    @elseif($isWon)
        <div class="flex items-center justify-center p-6 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
            <span class="text-4xl ml-4">🏆</span>
            <div>
                <span class="text-green-700 dark:text-green-300 font-bold text-xl block">
                    مبروك! تم الفوز بالعطاء
                </span>
                @if($getRecord()->winning_price)
                    <span class="text-green-600 dark:text-green-400 text-sm">
                        قيمة العقد: {{ number_format($getRecord()->winning_price, 2) }} JOD
                    </span>
                @endif
            </div>
        </div>
    
    {{-- حالة الخسارة --}}
    @elseif($isLost)
        <div class="flex items-center justify-center p-6 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-200 dark:border-orange-800">
            <x-heroicon-o-exclamation-triangle class="w-10 h-10 text-orange-500 ml-4" />
            <div>
                <span class="text-orange-700 dark:text-orange-300 font-bold text-lg block">
                    خسارة العطاء
                </span>
                @if($getRecord()->loss_reason)
                    <span class="text-orange-500 dark:text-orange-400 text-sm">
                        {{ $getRecord()->loss_reason }}
                    </span>
                @endif
            </div>
        </div>
    
    @else
        {{-- شريط التقدم العادي --}}
        <div class="relative">
            {{-- الخط الخلفي --}}
            <div class="absolute top-6 right-6 left-6 h-1 bg-gray-200 dark:bg-gray-700 rounded-full z-0"></div>
            {{-- الخط الملون (التقدم) --}}
            <div class="absolute top-6 right-6 h-1 bg-primary-500 rounded-full z-10 transition-all duration-500"
                 style="width: {{ ($currentIndex / (count($stages) - 1)) * (100 - 10) }}%;"></div>
            
            <div class="relative flex items-start justify-between z-20">
                @foreach($stages as $index => $stage)
                    @php
                        $isPassed = $index < $currentIndex;
                        $isCurrent = $index === $currentIndex;
                        $isFuture = $index > $currentIndex;
                    @endphp
                    
                    <div class="flex flex-col items-center group cursor-pointer" style="width: {{ 100 / count($stages) }}%;">
                        {{-- الدائرة والأيقونة --}}
                        <div class="flex items-center justify-center w-12 h-12 rounded-full transition-all duration-300 shadow-md
                            {{ $isPassed ? 'bg-success-500 hover:bg-success-600' : '' }}
                            {{ $isCurrent ? 'bg-primary-500 ring-4 ring-primary-200 dark:ring-primary-800 animate-pulse' : '' }}
                            {{ $isFuture ? 'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600' : '' }}">
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
                        <span class="mt-2 text-sm font-semibold text-center transition-colors
                            {{ $isPassed ? 'text-success-600 dark:text-success-400' : '' }}
                            {{ $isCurrent ? 'text-primary-600 dark:text-primary-400' : '' }}
                            {{ $isFuture ? 'text-gray-400 dark:text-gray-500' : '' }}">
                            {{ $stage['label'] }}
                        </span>
                        
                        {{-- الوصف (يظهر عند hover) --}}
                        <span class="mt-1 text-xs text-center opacity-0 group-hover:opacity-100 transition-opacity
                            {{ $isCurrent ? 'text-primary-500' : 'text-gray-400' }}">
                            {{ $stage['description'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
        
        {{-- معلومات إضافية --}}
        <div class="flex items-center justify-center gap-4 mt-6">
            {{-- الحالة الحالية --}}
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium
                {{ $currentStatus->getColor() === 'success' ? 'bg-success-100 text-success-700 dark:bg-success-900/20 dark:text-success-300' : '' }}
                {{ $currentStatus->getColor() === 'warning' ? 'bg-warning-100 text-warning-700 dark:bg-warning-900/20 dark:text-warning-300' : '' }}
                {{ $currentStatus->getColor() === 'danger' ? 'bg-danger-100 text-danger-700 dark:bg-danger-900/20 dark:text-danger-300' : '' }}
                {{ $currentStatus->getColor() === 'info' ? 'bg-info-100 text-info-700 dark:bg-info-900/20 dark:text-info-300' : '' }}
                {{ $currentStatus->getColor() === 'primary' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300' : '' }}
                {{ !in_array($currentStatus->getColor(), ['success', 'warning', 'danger', 'info', 'primary']) ? 'bg-gray-100 text-gray-700 dark:bg-gray-900/20 dark:text-gray-300' : '' }}">
                <span class="w-2 h-2 rounded-full animate-pulse
                    {{ $currentStatus->getColor() === 'success' ? 'bg-success-500' : '' }}
                    {{ $currentStatus->getColor() === 'warning' ? 'bg-warning-500' : '' }}
                    {{ $currentStatus->getColor() === 'danger' ? 'bg-danger-500' : '' }}
                    {{ $currentStatus->getColor() === 'info' ? 'bg-info-500' : '' }}
                    {{ $currentStatus->getColor() === 'primary' ? 'bg-primary-500' : '' }}
                    {{ !in_array($currentStatus->getColor(), ['success', 'warning', 'danger', 'info', 'primary']) ? 'bg-gray-500' : '' }}">
                </span>
                {{ $currentStatus->getLabel() }}
            </span>
            
            {{-- نسبة الإنجاز --}}
            <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                📊 {{ $progressPercentage }}% مكتمل
            </span>
            
            {{-- الأيام المتبقية --}}
            @if($getRecord()->submission_deadline && $getRecord()->days_until_submission !== null)
                @php $days = $getRecord()->days_until_submission; @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium
                    {{ $days < 0 ? 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-300' : '' }}
                    {{ $days >= 0 && $days <= 7 ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/20 dark:text-orange-300' : '' }}
                    {{ $days > 7 ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-300' : '' }}">
                    ⏰ {{ $days < 0 ? 'متأخر ' . abs($days) . ' يوم' : ($days == 0 ? 'اليوم!' : $days . ' يوم متبقي') }}
                </span>
            @endif
        </div>
    @endif
</div>
