<x-filament-panels::page>
    @if($workflowDefinition)
        {{-- معلومات سير العمل --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $workflowDefinition->name }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $workflowDefinition->description }}</p>
                    <div class="flex gap-3 mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                            {{ $workflowDefinition->code }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $workflowDefinition->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $workflowDefinition->is_active ? 'فعّال' : 'معطل' }}
                        </span>
                    </div>
                </div>
                <x-filament::button wire:click="addStep" icon="heroicon-o-plus">
                    إضافة خطوة
                </x-filament::button>
            </div>
        </div>

        {{-- الخطوات --}}
        <div class="space-y-4">
            @forelse($steps as $index => $step)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-r-4 
                    @if($step['is_final']) border-green-500 @else border-blue-500 @endif
                    hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            {{-- رقم الخطوة --}}
                            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $index + 1 }}</span>
                            </div>

                            {{-- معلومات الخطوة --}}
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $step['name'] }}</h3>
                                @if($step['description'])
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $step['description'] }}</p>
                                @endif
                                <div class="flex gap-2 mt-1">
                                    {{-- نوع الخطوة --}}
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        @switch($step['step_type'])
                                            @case('action') إجراء @break
                                            @case('approval') موافقة @break
                                            @case('review') مراجعة @break
                                            @case('notification') إشعار @break
                                            @default {{ $step['step_type'] }}
                                        @endswitch
                                    </span>
                                    
                                    {{-- نوع التعيين --}}
                                    @php
                                        $assignmentType = $step['assignment_type'] ?? 'role';
                                        $assignmentColor = match($assignmentType) {
                                            'role' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                            'team' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                            'user' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                            'dynamic' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                            default => 'bg-gray-100 text-gray-800',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $assignmentColor }}">
                                        @switch($assignmentType)
                                            @case('role')
                                                <x-heroicon-o-shield-check class="w-3 h-3 me-1" />
                                                {{ $step['role_name'] ?? 'دور' }}
                                                @break
                                            @case('team')
                                                <x-heroicon-o-user-group class="w-3 h-3 me-1" />
                                                {{ $step['team_name'] ?? 'فريق' }}
                                                @break
                                            @case('user')
                                                <x-heroicon-o-user class="w-3 h-3 me-1" />
                                                {{ $step['user_name'] ?? 'مستخدم' }}
                                                @break
                                            @case('dynamic')
                                                <x-heroicon-o-arrows-right-left class="w-3 h-3 me-1" />
                                                @switch($step['dynamic_assignment'])
                                                    @case('direct_manager') المدير المباشر @break
                                                    @case('department_head') رئيس القسم @break
                                                    @case('branch_manager') مدير الفرع @break
                                                    @case('creator') منشئ الطلب @break
                                                    @default ديناميكي
                                                @endswitch
                                                @break
                                        @endswitch
                                    </span>

                                    @if($step['is_final'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                            <x-heroicon-o-check-circle class="w-3 h-3 me-1" />
                                            نهائية
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- الإجراءات --}}
                        <div class="flex items-center gap-2">
                            {{-- أيقونات الخيارات --}}
                            <div class="flex gap-1 text-gray-400">
                                @if($step['allow_delegation'])
                                    <x-heroicon-o-arrow-path-rounded-square class="w-4 h-4" title="يسمح بالتفويض" />
                                @endif
                                @if($step['notify_on_assignment'])
                                    <x-heroicon-o-bell class="w-4 h-4" title="إشعار عند التعيين" />
                                @endif
                                @if($step['escalation_hours'])
                                    <x-heroicon-o-clock class="w-4 h-4" title="تصعيد بعد {{ $step['escalation_hours'] }} ساعة" />
                                @endif
                            </div>

                            {{-- أزرار التحكم --}}
                            <div class="flex gap-1 ms-4">
                                @if($index > 0)
                                    <x-filament::icon-button 
                                        wire:click="moveStepUp({{ $index }})"
                                        icon="heroicon-o-chevron-up"
                                        color="gray"
                                        size="sm"
                                        tooltip="تحريك لأعلى"
                                    />
                                @endif
                                @if($index < count($steps) - 1)
                                    <x-filament::icon-button 
                                        wire:click="moveStepDown({{ $index }})"
                                        icon="heroicon-o-chevron-down"
                                        color="gray"
                                        size="sm"
                                        tooltip="تحريك لأسفل"
                                    />
                                @endif
                                <x-filament::icon-button 
                                    wire:click="editStep({{ $index }})"
                                    icon="heroicon-o-pencil"
                                    color="warning"
                                    size="sm"
                                    tooltip="تعديل"
                                />
                                <x-filament::icon-button 
                                    wire:click="deleteStep({{ $index }})"
                                    wire:confirm="هل أنت متأكد من حذف هذه الخطوة؟"
                                    icon="heroicon-o-trash"
                                    color="danger"
                                    size="sm"
                                    tooltip="حذف"
                                />
                            </div>
                        </div>
                    </div>

                    {{-- خط الربط مع الخطوة التالية --}}
                    @if($index < count($steps) - 1)
                        <div class="flex justify-center my-2">
                            <x-heroicon-o-arrow-down class="w-5 h-5 text-gray-300" />
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-12 text-center">
                    <x-heroicon-o-queue-list class="w-16 h-16 mx-auto text-gray-400" />
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">لا توجد خطوات</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">ابدأ بإضافة خطوات لسير العمل</p>
                    <x-filament::button wire:click="addStep" icon="heroicon-o-plus" class="mt-4">
                        إضافة أول خطوة
                    </x-filament::button>
                </div>
            @endforelse
        </div>

        {{-- Modal تعديل/إضافة خطوة --}}
        <x-filament::modal
            id="step-modal"
            :heading="$editingStepId ? 'تعديل الخطوة' : 'إضافة خطوة جديدة'"
            width="3xl"
        >
            <div class="space-y-4">
                {{-- اسم الخطوة ونوعها --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اسم الخطوة *</label>
                        <input type="text" wire:model="name" 
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            placeholder="أدخل اسم الخطوة">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">نوع الخطوة</label>
                        <select wire:model="step_type" 
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="action">إجراء</option>
                            <option value="approval">موافقة</option>
                            <option value="review">مراجعة</option>
                            <option value="notification">إشعار</option>
                        </select>
                    </div>
                </div>

                {{-- الوصف --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الوصف</label>
                    <textarea wire:model="description" rows="2"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        placeholder="وصف الخطوة (اختياري)"></textarea>
                </div>

                {{-- التعيين --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">التعيين</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">طريقة التعيين *</label>
                            <select wire:model.live="assignment_type" 
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="role">دور وظيفي</option>
                                <option value="team">فريق عمل</option>
                                <option value="user">مستخدم محدد</option>
                                <option value="dynamic">ديناميكي</option>
                            </select>
                        </div>

                        @if($assignment_type === 'role')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الدور</label>
                            <select wire:model="assigned_role_id" 
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">-- اختر الدور --</option>
                                @foreach(\App\Models\Role::pluck('name_ar', 'id') as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        @if($assignment_type === 'team')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الفريق</label>
                            <select wire:model="assigned_team_id" 
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">-- اختر الفريق --</option>
                                @foreach(\App\Models\Team::where('is_active', true)->pluck('name_ar', 'id') as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        @if($assignment_type === 'user')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المستخدم</label>
                            <select wire:model="assigned_user_id" 
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">-- اختر المستخدم --</option>
                                @foreach(\App\Models\User::where('is_active', true)->pluck('name', 'id') as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        @if($assignment_type === 'dynamic')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">التعيين الديناميكي</label>
                            <select wire:model="dynamic_assignment" 
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">-- اختر --</option>
                                <option value="direct_manager">المدير المباشر</option>
                                <option value="department_head">رئيس القسم</option>
                                <option value="branch_manager">مدير الفرع</option>
                                <option value="creator">منشئ الطلب</option>
                            </select>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- الخيارات --}}
                <div class="grid grid-cols-3 gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="allow_delegation" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">السماح بالتفويض</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="notify_on_assignment" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">إشعار عند التعيين</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="is_final" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">خطوة نهائية</span>
                    </label>
                </div>

                {{-- التصعيد --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">التصعيد (اختياري)</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ساعات قبل التصعيد</label>
                            <input type="number" wire:model="escalation_hours" 
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                placeholder="اتركه فارغاً لعدم التصعيد">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">تصعيد إلى</label>
                            <select wire:model="escalate_to_role_id" 
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">-- اختر الدور --</option>
                                @foreach(\App\Models\Role::pluck('name_ar', 'id') as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <x-slot name="footerActions">
                <x-filament::button wire:click="closeModal" color="gray">
                    إلغاء
                </x-filament::button>
                <x-filament::button wire:click="saveStep" color="primary">
                    حفظ الخطوة
                </x-filament::button>
            </x-slot>
        </x-filament::modal>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-12 text-center">
            <x-heroicon-o-exclamation-triangle class="w-16 h-16 mx-auto text-yellow-500" />
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">لم يتم تحديد سير عمل</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">يرجى اختيار سير عمل من قائمة سير العمل</p>
            <x-filament::button 
                :href="route('filament.admin.pages.access-management', ['activeTab' => 'workflows'])" 
                tag="a"
                icon="heroicon-o-arrow-right" 
                class="mt-4"
            >
                العودة لقائمة سير العمل
            </x-filament::button>
        </div>
    @endif
</x-filament-panels::page>
