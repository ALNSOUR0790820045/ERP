<div class="p-4 space-y-4">
    {{-- معلومات الدور --}}
    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-gray-700 dark:to-gray-800 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-full">
                <x-heroicon-o-shield-check class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
                <h3 class="font-bold text-lg text-gray-900 dark:text-white">{{ $role->name_ar }}</h3>
                <p class="text-sm text-gray-500">المستوى: {{ $role->level }} | الرمز: {{ $role->code }}</p>
            </div>
        </div>
    </div>

    {{-- الوحدات والشاشات --}}
    @if($modules->isEmpty())
        <div class="text-center py-8 text-gray-500">
            <x-heroicon-o-squares-2x2 class="w-12 h-12 mx-auto text-gray-400" />
            <p class="mt-2">لم يتم تحديد وحدات لهذا الدور</p>
            <p class="text-sm">يرجى تعديل الدور وإضافة الوحدات المطلوبة</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($modules as $module)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    {{-- رأس الوحدة --}}
                    <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 flex items-center gap-3">
                        <x-dynamic-component 
                            :component="$module->icon ?? 'heroicon-o-squares-2x2'" 
                            class="w-5 h-5 text-{{ $module->color ?? 'gray' }}-600" 
                        />
                        <span class="font-medium text-gray-900 dark:text-white">{{ $module->name_ar }}</span>
                        <span class="text-xs text-gray-500">({{ $module->screens->count() }} شاشة)</span>
                    </div>
                    
                    {{-- الشاشات --}}
                    @if($module->screens->isNotEmpty())
                        <div class="p-3">
                            <div class="flex flex-wrap gap-2">
                                @foreach($module->screens as $screen)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        <x-heroicon-o-check class="w-3 h-3" />
                                        {{ $screen->name_ar }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
