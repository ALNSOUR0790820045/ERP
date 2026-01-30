<x-filament-panels::page>
    {{-- Status Progress Bar --}}
    @if($this->record->exists)
    <div class="mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    <span class="text-primary-600">{{ $this->record->tender_number ?? 'جديد' }}</span>
                    - {{ $this->record->name_ar }}
                </h3>
                <span class="px-3 py-1 text-sm font-medium rounded-full 
                    {{ $this->record->status->getColor() === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                    {{ $this->record->status->getColor() === 'warning' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                    {{ $this->record->status->getColor() === 'danger' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                    {{ $this->record->status->getColor() === 'info' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                    {{ $this->record->status->getColor() === 'gray' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' : '' }}
                ">
                    {{ $this->record->status->getLabel() }}
                </span>
            </div>
            
            {{-- Quick Info Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-xs text-gray-500 dark:text-gray-400">نوع العطاء</div>
                    <div class="font-semibold text-gray-900 dark:text-white">{{ $this->record->tender_type?->getLabel() ?? '-' }}</div>
                </div>
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-xs text-gray-500 dark:text-gray-400">أسلوب الطرح</div>
                    <div class="font-semibold text-gray-900 dark:text-white">{{ $this->record->tender_method?->getLabel() ?? '-' }}</div>
                </div>
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-xs text-gray-500 dark:text-gray-400">المالك</div>
                    <div class="font-semibold text-gray-900 dark:text-white">{{ $this->record->owner?->name_ar ?? $this->record->owner_name ?? '-' }}</div>
                </div>
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-xs text-gray-500 dark:text-gray-400">موعد التقديم</div>
                    <div class="font-semibold text-gray-900 dark:text-white">
                        {{ $this->record->submission_deadline ? $this->record->submission_deadline->format('Y-m-d') : '-' }}
                    </div>
                </div>
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-xs text-gray-500 dark:text-gray-400">القيمة التقديرية</div>
                    <div class="font-semibold text-gray-900 dark:text-white">
                        {{ $this->record->estimated_value ? number_format($this->record->estimated_value) . ' د.أ' : '-' }}
                    </div>
                </div>
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-xs text-gray-500 dark:text-gray-400">الأيام المتبقية</div>
                    <div class="font-semibold {{ $this->record->submission_deadline && $this->record->submission_deadline->isPast() ? 'text-red-600' : 'text-green-600' }}">
                        @if($this->record->submission_deadline)
                            @if($this->record->submission_deadline->isPast())
                                انتهى
                            @else
                                {{ $this->record->submission_deadline->diffInDays(now()) }} يوم
                            @endif
                        @else
                            -
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Main Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
        <form wire:submit="save">
            {{ $this->form }}
            
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                <x-filament::button 
                    type="submit"
                    icon="heroicon-o-check"
                    color="primary"
                >
                    حفظ
                </x-filament::button>
                
                @if($this->record->exists)
                    <x-filament::button 
                        type="button"
                        icon="heroicon-o-arrow-left"
                        color="gray"
                        :href="\App\Filament\Resources\TenderResource::getUrl('index')"
                        tag="a"
                    >
                        العودة للقائمة
                    </x-filament::button>
                @endif
            </div>
        </form>
    </div>

    {{-- Relations Section (only for existing records) --}}
    @if($this->record->exists)
    <div class="mt-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">العلاقات والتفاصيل</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ \App\Filament\Resources\TenderResource::getUrl('view', ['record' => $this->record]) }}#boq-items" 
                   class="flex flex-col items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <x-heroicon-o-table-cells class="w-8 h-8 text-primary-600 mb-2"/>
                    <span class="font-medium text-gray-900 dark:text-white">جدول الكميات</span>
                    <span class="text-sm text-gray-500">{{ $this->record->boqItems?->count() ?? 0 }} بند</span>
                </a>
                <a href="{{ \App\Filament\Resources\TenderResource::getUrl('view', ['record' => $this->record]) }}#documents" 
                   class="flex flex-col items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <x-heroicon-o-document-text class="w-8 h-8 text-primary-600 mb-2"/>
                    <span class="font-medium text-gray-900 dark:text-white">الوثائق</span>
                    <span class="text-sm text-gray-500">{{ $this->record->documents?->count() ?? 0 }} وثيقة</span>
                </a>
                <a href="{{ \App\Filament\Resources\TenderResource::getUrl('view', ['record' => $this->record]) }}#bonds" 
                   class="flex flex-col items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <x-heroicon-o-banknotes class="w-8 h-8 text-primary-600 mb-2"/>
                    <span class="font-medium text-gray-900 dark:text-white">الكفالات</span>
                    <span class="text-sm text-gray-500">{{ $this->record->bonds?->count() ?? 0 }} كفالة</span>
                </a>
                <a href="{{ \App\Filament\Resources\TenderResource::getUrl('view', ['record' => $this->record]) }}#competitors" 
                   class="flex flex-col items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <x-heroicon-o-users class="w-8 h-8 text-primary-600 mb-2"/>
                    <span class="font-medium text-gray-900 dark:text-white">المنافسون</span>
                    <span class="text-sm text-gray-500">{{ $this->record->competitors?->count() ?? 0 }} منافس</span>
                </a>
            </div>
        </div>
    </div>
    @endif
</x-filament-panels::page>
