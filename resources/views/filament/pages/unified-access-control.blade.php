<x-filament-panels::page>
    @php 
        $stats = $this->getStats(); 
        $dashboardData = $this->getDashboardData(); 
    @endphp

    {{-- لوحة المعلومات --}}
    @if($mainTab === 'dashboard')
        {{-- الإحصائيات الرئيسية --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            {{-- المستخدمين --}}
            <x-filament::section>
                <div class="flex items-center justify-between cursor-pointer" wire:click="setMainTab('users')">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">المستخدمين</p>
                        <p class="text-3xl font-bold" style="color: #3b82f6;">{{ $stats['users']['total'] }}</p>
                        <div class="mt-2 flex gap-2 text-xs">
                            <x-filament::badge color="success">{{ $stats['users']['active'] }} نشط</x-filament::badge>
                            <x-filament::badge color="danger">{{ $stats['users']['inactive'] }} معطل</x-filament::badge>
                        </div>
                    </div>
                    <div class="p-3 rounded-xl" style="background-color: #dbeafe;">
                        <x-heroicon-o-users class="w-8 h-8" style="color: #3b82f6;" />
                    </div>
                </div>
            </x-filament::section>

            {{-- الأدوار --}}
            <x-filament::section>
                <div class="flex items-center justify-between cursor-pointer" wire:click="setMainTab('roles')">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">الأدوار</p>
                        <p class="text-3xl font-bold" style="color: #f59e0b;">{{ $stats['roles']['total'] }}</p>
                        <div class="mt-2 flex flex-wrap gap-1 text-xs">
                            <x-filament::badge color="warning">💼 {{ $stats['roles']['job'] }} وظيفي</x-filament::badge>
                            <x-filament::badge color="success">📋 {{ $stats['roles']['tender'] }} عطاءات</x-filament::badge>
                        </div>
                    </div>
                    <div class="p-3 rounded-xl" style="background-color: #fef3c7;">
                        <x-heroicon-o-shield-check class="w-8 h-8" style="color: #f59e0b;" />
                    </div>
                </div>
            </x-filament::section>

            {{-- الوحدات والشاشات --}}
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">الوحدات</p>
                        <p class="text-3xl font-bold" style="color: #10b981;">{{ $stats['modules']['total'] }}</p>
                        <div class="mt-2">
                            <x-filament::badge color="info">{{ $stats['modules']['screens'] }} شاشة</x-filament::badge>
                        </div>
                    </div>
                    <div class="p-3 rounded-xl" style="background-color: #d1fae5;">
                        <x-heroicon-o-squares-2x2 class="w-8 h-8" style="color: #10b981;" />
                    </div>
                </div>
            </x-filament::section>

            {{-- سير العمل --}}
            <x-filament::section>
                <div class="flex items-center justify-between cursor-pointer" wire:click="setMainTab('workflows')">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">سير العمل</p>
                        <p class="text-3xl font-bold" style="color: #8b5cf6;">{{ $stats['workflows']['total'] }}</p>
                        <div class="mt-2 flex gap-1">
                            <x-filament::badge color="success">{{ $stats['workflows']['active'] }} فعّال</x-filament::badge>
                            <x-filament::badge color="gray">{{ $stats['workflows']['steps'] }} خطوة</x-filament::badge>
                        </div>
                    </div>
                    <div class="p-3 rounded-xl" style="background-color: #ede9fe;">
                        <x-heroicon-o-arrow-path class="w-8 h-8" style="color: #8b5cf6;" />
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- محتوى لوحة المعلومات --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- أحدث المستخدمين --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-user-plus class="w-5 h-5" style="color: #3b82f6;" />
                        <span>أحدث المستخدمين</span>
                    </div>
                </x-slot>
                
                <div class="divide-y dark:divide-gray-700">
                    @forelse($dashboardData['recent_users'] as $user)
                        <div class="py-3 flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=random" 
                                 alt="" class="w-10 h-10 rounded-full">
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 dark:text-white truncate">{{ $user->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $user->email }}</p>
                            </div>
                            @if($user->role)
                                <x-filament::badge color="warning">{{ $user->role->name_ar }}</x-filament::badge>
                            @endif
                        </div>
                    @empty
                        <div class="py-4 text-center text-gray-500">لا يوجد مستخدمين</div>
                    @endforelse
                </div>
            </x-filament::section>

            {{-- الأدوار الأكثر استخداماً --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-chart-bar class="w-5 h-5" style="color: #f59e0b;" />
                        <span>الأدوار الأكثر استخداماً</span>
                    </div>
                </x-slot>
                
                <div class="space-y-4">
                    @forelse($dashboardData['roles_summary'] as $role)
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $role->name_ar }}</span>
                                <span class="text-sm text-gray-500">{{ $role->users_count }} مستخدم</span>
                            </div>
                            @php $maxUsers = $dashboardData['roles_summary']->max('users_count') ?: 1; @endphp
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full" style="width: {{ ($role->users_count / $maxUsers) * 100 }}%; background-color: #f59e0b;"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-gray-500">لا توجد أدوار</div>
                    @endforelse
                </div>
            </x-filament::section>

            {{-- سير العمل النشط --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-arrow-path class="w-5 h-5" style="color: #8b5cf6;" />
                        <span>سير العمل النشط</span>
                    </div>
                </x-slot>
                
                <div class="divide-y dark:divide-gray-700">
                    @forelse($dashboardData['active_workflows'] as $workflow)
                        <div class="py-3 flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $workflow->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $workflow->steps_count }} خطوة</p>
                            </div>
                            <a href="{{ route('filament.admin.pages.workflow-designer', ['workflow' => $workflow->id]) }}"
                               style="color: #3b82f6;">
                                <x-heroicon-o-arrow-top-right-on-square class="w-5 h-5" />
                            </a>
                        </div>
                    @empty
                        <div class="py-4 text-center text-gray-500">لا يوجد سير عمل نشط</div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        {{-- روابط سريعة --}}
        <div class="mt-6">
            <x-filament::section>
                <x-slot name="heading">روابط سريعة</x-slot>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="{{ route('filament.admin.pages.stage-permission-manager') }}"
                       class="flex items-center gap-3 p-4 rounded-xl border-2 transition-all hover:shadow-lg"
                       style="background-color: #faf5ff; border-color: #c4b5fd;">
                        <x-heroicon-o-adjustments-horizontal class="w-8 h-8" style="color: #8b5cf6;" />
                        <div>
                            <p class="font-bold" style="color: #5b21b6;">صلاحيات المراحل</p>
                            <p class="text-xs" style="color: #7c3aed;">إدارة متقدمة</p>
                        </div>
                    </a>
                    
                    <a href="{{ route('filament.admin.pages.workflow-designer') }}"
                       class="flex items-center gap-3 p-4 rounded-xl border-2 transition-all hover:shadow-lg"
                       style="background-color: #eff6ff; border-color: #93c5fd;">
                        <x-heroicon-o-cog-6-tooth class="w-8 h-8" style="color: #3b82f6;" />
                        <div>
                            <p class="font-bold" style="color: #1e40af;">مصمم سير العمل</p>
                            <p class="text-xs" style="color: #3b82f6;">تصميم الخطوات</p>
                        </div>
                    </a>
                    
                    <button wire:click="setMainTab('templates')"
                       class="flex items-center gap-3 p-4 rounded-xl border-2 transition-all hover:shadow-lg text-right"
                       style="background-color: #ecfdf5; border-color: #86efac;">
                        <x-heroicon-o-document-duplicate class="w-8 h-8" style="color: #10b981;" />
                        <div>
                            <p class="font-bold" style="color: #065f46;">قوالب الصلاحيات</p>
                            <p class="text-xs" style="color: #10b981;">قوالب جاهزة</p>
                        </div>
                    </button>
                    
                    <a href="{{ route('filament.admin.resources.teams.index') }}"
                       class="flex items-center gap-3 p-4 rounded-xl border-2 transition-all hover:shadow-lg"
                       style="background-color: #fdf2f8; border-color: #f9a8d4;">
                        <x-heroicon-o-user-group class="w-8 h-8" style="color: #ec4899;" />
                        <div>
                            <p class="font-bold" style="color: #9d174d;">فرق العمل</p>
                            <p class="text-xs" style="color: #ec4899;">إدارة الفرق</p>
                        </div>
                    </a>
                </div>
            </x-filament::section>
        </div>
    @endif

    {{-- صفحات الجداول --}}
    @if(in_array($mainTab, ['users', 'roles', 'workflows', 'templates']))
        {{-- التنقل بين التبويبات --}}
        <x-filament::tabs class="mb-6">
            <x-filament::tabs.item 
                :active="$mainTab === 'dashboard'"
                wire:click="setMainTab('dashboard')"
                icon="heroicon-o-home">
                لوحة المعلومات
            </x-filament::tabs.item>
            
            <x-filament::tabs.item 
                :active="$mainTab === 'users'"
                wire:click="setMainTab('users')"
                icon="heroicon-o-users">
                المستخدمين
            </x-filament::tabs.item>
            
            <x-filament::tabs.item 
                :active="$mainTab === 'roles'"
                wire:click="setMainTab('roles')"
                icon="heroicon-o-shield-check">
                الأدوار
            </x-filament::tabs.item>
            
            <x-filament::tabs.item 
                :active="$mainTab === 'workflows'"
                wire:click="setMainTab('workflows')"
                icon="heroicon-o-arrow-path">
                سير العمل
            </x-filament::tabs.item>
            
            <x-filament::tabs.item 
                :active="$mainTab === 'templates'"
                wire:click="setMainTab('templates')"
                icon="heroicon-o-document-duplicate">
                القوالب
            </x-filament::tabs.item>
        </x-filament::tabs>

        {{-- الجدول --}}
        {{ $this->table }}
    @endif

    {{-- صفحة إدارة الصلاحيات --}}
    @if($mainTab === 'permissions' && ($selectedUserId || $selectedRoleId))
        {{-- رأس الصفحة --}}
        <x-filament::section class="mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    @if($selectedUserId)
                        @php $user = \App\Models\User::find($selectedUserId); @endphp
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($user?->name ?? '') }}&background=6366f1&color=fff&size=80" 
                             alt="" class="w-16 h-16 rounded-full border-4" style="border-color: #c7d2fe;">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $user?->name }}</h2>
                            <p class="text-gray-500 dark:text-gray-400">{{ $user?->email }}</p>
                            <div class="mt-2 flex gap-2">
                                @foreach($user?->roles ?? [] as $role)
                                    <x-filament::badge color="primary">{{ $role->name_ar }}</x-filament::badge>
                                @endforeach
                            </div>
                        </div>
                    @else
                        @php $role = \App\Models\Role::find($selectedRoleId); @endphp
                        <div class="w-16 h-16 rounded-full flex items-center justify-center" style="background-color: #fef3c7;">
                            <x-heroicon-o-shield-check class="w-10 h-10" style="color: #f59e0b;" />
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $role?->name_ar }}</h2>
                            <p class="text-gray-500 dark:text-gray-400">{{ $role?->description ?? 'بدون وصف' }}</p>
                            <div class="mt-2">
                                <x-filament::badge color="info">{{ $role?->users_count ?? 0 }} مستخدم</x-filament::badge>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="text-left">
                    <p class="text-sm text-gray-500">إجمالي الصلاحيات الممنوحة</p>
                    <p class="text-3xl font-bold" style="color: #10b981;">{{ collect($permissionMatrix)->filter()->count() }}</p>
                </div>
            </div>
        </x-filament::section>

        {{-- اختيار الوحدة --}}
        <x-filament::section class="mb-6">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-squares-2x2 class="w-5 h-5" />
                    <span>اختر الوحدة</span>
                </div>
            </x-slot>
            
            <div class="flex flex-wrap gap-2">
                @foreach($this->getModules() as $module)
                    <x-filament::button 
                        :color="$selectedModuleId == $module->id ? 'primary' : 'gray'"
                        wire:click="selectModule({{ $module->id }})">
                        {{ $module->name_ar }}
                    </x-filament::button>
                @endforeach
            </div>
        </x-filament::section>

        {{-- مصفوفة الصلاحيات --}}
        @if($selectedModuleId)
            @php
                $stages = $this->getStages();
                $permissionTypes = $this->getPermissionTypes();
            @endphp
            
            <x-filament::section>
                <x-slot name="heading">مصفوفة الصلاحيات</x-slot>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800">
                                <th class="text-right py-4 px-6 font-bold text-gray-700 dark:text-gray-300 border-b dark:border-gray-600">
                                    المرحلة
                                </th>
                                @foreach($permissionTypes as $type)
                                    <th class="text-center py-4 px-4 font-medium text-gray-700 dark:text-gray-300 border-b dark:border-gray-600">
                                        <div class="flex flex-col items-center gap-1">
                                            @if($type->icon)
                                                <span class="text-xl">{{ $type->icon }}</span>
                                            @endif
                                            <span class="text-xs">{{ $type->name_ar }}</span>
                                        </div>
                                    </th>
                                @endforeach
                                <th class="text-center py-4 px-4 border-b dark:border-gray-600">
                                    <span class="text-xs font-medium text-gray-500">إجراءات</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stages as $stage)
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center font-bold" style="background-color: #dbeafe; color: #3b82f6;">
                                                {{ $stage->sort_order }}
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-white">{{ $stage->name_ar }}</p>
                                                @if($stage->description)
                                                    <p class="text-xs text-gray-500">{{ $stage->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    @foreach($permissionTypes as $type)
                                        @php $key = "{$stage->id}_{$type->id}"; @endphp
                                        <td class="text-center py-4 px-4">
                                            <button wire:click="togglePermission({{ $stage->id }}, {{ $type->id }})"
                                                    class="w-10 h-10 rounded-lg transition-all flex items-center justify-center mx-auto"
                                                    style="{{ ($permissionMatrix[$key] ?? false) ? 'background-color: #10b981; color: white; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);' : 'background-color: #e5e7eb; color: #9ca3af;' }}">
                                                @if($permissionMatrix[$key] ?? false)
                                                    <x-heroicon-o-check class="w-6 h-6" />
                                                @else
                                                    <x-heroicon-o-x-mark class="w-5 h-5" />
                                                @endif
                                            </button>
                                        </td>
                                    @endforeach
                                    <td class="text-center py-4 px-4">
                                        <div class="flex items-center justify-center gap-1">
                                            <x-filament::icon-button
                                                icon="heroicon-o-check-circle"
                                                color="success"
                                                size="sm"
                                                wire:click="grantAllForStage({{ $stage->id }})"
                                            />
                                            <x-filament::icon-button
                                                icon="heroicon-o-x-circle"
                                                color="danger"
                                                size="sm"
                                                wire:click="revokeAllForStage({{ $stage->id }})"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- دليل الألوان --}}
                <div class="mt-6 flex items-center justify-center gap-8 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded" style="background-color: #10b981;"></div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">صلاحية ممنوحة</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded" style="background-color: #e5e7eb;"></div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">غير ممنوحة</span>
                    </div>
                </div>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
