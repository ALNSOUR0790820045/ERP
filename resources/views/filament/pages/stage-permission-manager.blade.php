<x-filament-panels::page>
    <div class="space-y-6">
        {{-- اختيار الوحدة --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">اختر الوحدة</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($this->getModules() as $module)
                    <button
                        wire:click="selectModule({{ $module->id }})"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $selectedModuleId === $module->id ? 'bg-primary-600 text-white shadow-lg' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                    >
                        <span class="flex items-center gap-2">
                            @if($module->icon)
                                <x-dynamic-component :component="$module->icon" class="w-5 h-5" />
                            @endif
                            {{ $module->name_ar }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- التبويبات: مستخدمين / أدوار --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex -mb-px">
                    <button
                        wire:click="setActiveTab('users')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'users' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        <span class="flex items-center gap-2">
                            <x-heroicon-o-users class="w-5 h-5" />
                            المستخدمين
                        </span>
                    </button>
                    <button
                        wire:click="setActiveTab('roles')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'roles' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        <span class="flex items-center gap-2">
                            <x-heroicon-o-shield-check class="w-5 h-5" />
                            الأدوار
                        </span>
                    </button>
                </nav>
            </div>

            <div class="p-4">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    {{-- قائمة المستخدمين/الأدوار --}}
                    <div class="lg:col-span-1">
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 max-h-96 overflow-y-auto">
                            @if($activeTab === 'users')
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">المستخدمين</h4>
                                <div class="space-y-1">
                                    @foreach($this->getUsers() as $user)
                                        <button
                                            wire:click="selectUser({{ $user->id }})"
                                            class="w-full text-right px-3 py-2 rounded-lg text-sm transition-colors {{ $selectedUserId === $user->id ? 'bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300' : 'hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300' }}"
                                        >
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-xs font-medium">
                                                    {{ mb_substr($user->name, 0, 1) }}
                                                </div>
                                                <div class="flex-1 text-right">
                                                    <div class="font-medium">{{ $user->name }}</div>
                                                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                                </div>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @else
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">الأدوار</h4>
                                <div class="space-y-1">
                                    @foreach($this->getRoles() as $role)
                                        <button
                                            wire:click="selectRole({{ $role->id }})"
                                            class="w-full text-right px-3 py-2 rounded-lg text-sm transition-colors {{ $selectedRoleId === $role->id ? 'bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300' : 'hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300' }}"
                                        >
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-o-shield-check class="w-5 h-5 text-gray-400" />
                                                <span>{{ $role->name }}</span>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- مصفوفة الصلاحيات --}}
                    <div class="lg:col-span-3">
                        @if($selectedModuleId && (($activeTab === 'users' && $selectedUserId) || ($activeTab === 'roles' && $selectedRoleId)))
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        صلاحيات {{ $activeTab === 'users' ? $this->getSelectedUserName() : $this->getSelectedRoleName() }}
                                    </h4>
                                    <p class="text-sm text-gray-500">على وحدة {{ $this->getSelectedModuleName() }}</p>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr class="bg-gray-100 dark:bg-gray-700">
                                            <th class="px-4 py-3 text-right text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 min-w-[150px]">
                                                المرحلة
                                            </th>
                                            @foreach($this->getPermissionTypes() as $type)
                                                <th class="px-2 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600" title="{{ $type->description }}">
                                                    <div class="flex flex-col items-center gap-1">
                                                        @if($type->icon)
                                                            <x-dynamic-component :component="$type->icon" class="w-4 h-4" />
                                                        @endif
                                                        <span>{{ $type->name_ar }}</span>
                                                    </div>
                                                </th>
                                            @endforeach
                                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                                إجراءات
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($this->getStages() as $stage)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                                <td class="px-4 py-3 border border-gray-200 dark:border-gray-600">
                                                    <div class="flex items-center gap-2">
                                                        @if($stage->color)
                                                            <span class="w-3 h-3 rounded-full" style="background-color: {{ $stage->color }}"></span>
                                                        @endif
                                                        <span class="font-medium text-gray-900 dark:text-white">{{ $stage->name_ar }}</span>
                                                        @if($this->isStageVisible($stage->id))
                                                            <x-heroicon-s-eye class="w-4 h-4 text-green-500" title="مرئية" />
                                                        @else
                                                            <x-heroicon-s-eye-slash class="w-4 h-4 text-gray-400" title="مخفية" />
                                                        @endif
                                                    </div>
                                                </td>
                                                @foreach($this->getPermissionTypes() as $type)
                                                    @php
                                                        $key = "{$stage->id}_{$type->id}";
                                                        $hasPermission = $permissionMatrix[$key] ?? false;
                                                    @endphp
                                                    <td class="px-2 py-3 text-center border border-gray-200 dark:border-gray-600">
                                                        <button
                                                            wire:click="togglePermission({{ $stage->id }}, {{ $type->id }})"
                                                            class="w-6 h-6 rounded transition-colors {{ $hasPermission ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-400' }} hover:opacity-80"
                                                        >
                                                            @if($hasPermission)
                                                                <x-heroicon-s-check class="w-4 h-4 mx-auto" />
                                                            @else
                                                                <x-heroicon-s-minus class="w-4 h-4 mx-auto" />
                                                            @endif
                                                        </button>
                                                    </td>
                                                @endforeach
                                                <td class="px-2 py-3 text-center border border-gray-200 dark:border-gray-600">
                                                    <div class="flex items-center justify-center gap-1">
                                                        <button
                                                            wire:click="grantAllStagePermissions({{ $stage->id }})"
                                                            class="p-1 rounded bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-800"
                                                            title="منح الكل"
                                                        >
                                                            <x-heroicon-s-check-circle class="w-4 h-4" />
                                                        </button>
                                                        <button
                                                            wire:click="revokeAllStagePermissions({{ $stage->id }})"
                                                            class="p-1 rounded bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-800"
                                                            title="سحب الكل"
                                                        >
                                                            <x-heroicon-s-x-circle class="w-4 h-4" />
                                                        </button>
                                                        <button
                                                            wire:click="toggleStageVisibility({{ $stage->id }})"
                                                            class="p-1 rounded bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-800"
                                                            title="تبديل الرؤية"
                                                        >
                                                            <x-heroicon-s-eye class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- دليل الألوان --}}
                            <div class="mt-4 flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                                <div class="flex items-center gap-2">
                                    <span class="w-4 h-4 rounded bg-green-500"></span>
                                    <span>صلاحية ممنوحة</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-4 h-4 rounded bg-gray-200 dark:bg-gray-600"></span>
                                    <span>بدون صلاحية</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-s-eye class="w-4 h-4 text-green-500" />
                                    <span>المرحلة مرئية</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-s-eye-slash class="w-4 h-4 text-gray-400" />
                                    <span>المرحلة مخفية</span>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-12 text-gray-500">
                                <x-heroicon-o-shield-exclamation class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                                <p>اختر {{ $activeTab === 'users' ? 'مستخدم' : 'دور' }} ووحدة لعرض مصفوفة الصلاحيات</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- معلومات إضافية --}}
        @if($selectedModuleId && (($activeTab === 'users' && $selectedUserId) || ($activeTab === 'roles' && $selectedRoleId)))
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- إحصائيات --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">إحصائيات الصلاحيات</h4>
                    @php
                        $totalPermissions = count(array_filter($permissionMatrix));
                        $totalPossible = $this->getStages()->count() * $this->getPermissionTypes()->count();
                        $percentage = $totalPossible > 0 ? round(($totalPermissions / $totalPossible) * 100) : 0;
                    @endphp
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">الصلاحيات الممنوحة</span>
                            <span class="font-bold text-primary-600">{{ $totalPermissions }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">إجمالي الصلاحيات</span>
                            <span class="font-medium">{{ $totalPossible }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                            <div class="bg-primary-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                        <div class="text-center text-sm text-gray-600 dark:text-gray-400">{{ $percentage }}%</div>
                    </div>
                </div>

                {{-- أنواع الصلاحيات --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">أنواع الصلاحيات</h4>
                    <div class="space-y-2">
                        @foreach($this->getPermissionTypes()->take(6) as $type)
                            <div class="flex items-center gap-2 text-sm">
                                @if($type->icon)
                                    <x-dynamic-component :component="$type->icon" class="w-4 h-4 text-gray-400" />
                                @endif
                                <span class="text-gray-700 dark:text-gray-300">{{ $type->name_ar }}</span>
                                <span class="text-xs text-gray-500">({{ $type->code }})</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- القوالب المتاحة --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">قوالب سريعة</h4>
                    <div class="space-y-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            استخدم زر "تطبيق قالب" في أعلى الصفحة لتطبيق مجموعة صلاحيات محددة مسبقاً
                        </p>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded text-xs">سكرتير عطاءات</span>
                            <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded text-xs">مدير عطاءات</span>
                            <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs">مشاهد</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
