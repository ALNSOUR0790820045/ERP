<x-filament-panels::page>
    {{-- الإحصائيات --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        @php $stats = $this->getStats(); @endphp
        
        {{-- المستخدمين --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-r-4 border-blue-500 cursor-pointer hover:shadow-lg transition-shadow {{ $activeTab === 'users' ? 'ring-2 ring-blue-500' : '' }}"
             wire:click="setActiveTab('users')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">المستخدمين</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['users']['total'] }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <x-heroicon-o-users class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <div class="mt-2 flex gap-2 text-xs">
                <span class="text-green-600">{{ $stats['users']['active'] }} نشط</span>
                <span class="text-gray-400">|</span>
                <span class="text-red-600">{{ $stats['users']['inactive'] }} معطل</span>
            </div>
        </div>

        {{-- الأدوار --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-r-4 border-yellow-500 cursor-pointer hover:shadow-lg transition-shadow {{ $activeTab === 'roles' ? 'ring-2 ring-yellow-500' : '' }}"
             wire:click="setActiveTab('roles')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">الأدوار</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['roles']['total'] }}</p>
                </div>
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
                    <x-heroicon-o-shield-check class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                </div>
            </div>
            <div class="mt-2 flex gap-2 text-xs">
                <span class="text-blue-600">{{ $stats['roles']['system'] }} نظام</span>
                <span class="text-gray-400">|</span>
                <span class="text-gray-600">{{ $stats['roles']['custom'] }} مخصص</span>
            </div>
        </div>

        {{-- الفرق --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-r-4 border-green-500 cursor-pointer hover:shadow-lg transition-shadow {{ $activeTab === 'teams' ? 'ring-2 ring-green-500' : '' }}"
             wire:click="setActiveTab('teams')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">فرق العمل</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['teams']['total'] }}</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                    <x-heroicon-o-user-group class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <div class="mt-2 text-xs text-green-600">
                {{ $stats['teams']['active'] }} فريق نشط
            </div>
        </div>

        {{-- الصلاحيات --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-r-4 border-purple-500 cursor-pointer hover:shadow-lg transition-shadow {{ $activeTab === 'permissions' ? 'ring-2 ring-purple-500' : '' }}"
             wire:click="setActiveTab('permissions')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">الصلاحيات</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['permissions']['total'] }}</p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                    <x-heroicon-o-key class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <div class="mt-2 text-xs text-purple-600">
                {{ $stats['permissions']['modules'] }} وحدة
            </div>
        </div>

        {{-- سير العمل --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-r-4 border-indigo-500 cursor-pointer hover:shadow-lg transition-shadow {{ $activeTab === 'workflows' ? 'ring-2 ring-indigo-500' : '' }}"
             wire:click="setActiveTab('workflows')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">سير العمل</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['workflows']['total'] }}</p>
                </div>
                <div class="p-3 bg-indigo-100 dark:bg-indigo-900 rounded-full">
                    <x-heroicon-o-arrow-path class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                </div>
            </div>
            <div class="mt-2 text-xs text-indigo-600">
                {{ $stats['workflows']['active'] }} فعّال
            </div>
        </div>
    </div>

    {{-- التبويبات --}}
    <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
        <nav class="flex gap-4" aria-label="Tabs">
            <button wire:click="setActiveTab('users')"
                    class="py-2 px-4 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'users' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <x-heroicon-o-users class="w-4 h-4 inline-block me-1" />
                المستخدمين
            </button>
            <button wire:click="setActiveTab('roles')"
                    class="py-2 px-4 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'roles' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <x-heroicon-o-shield-check class="w-4 h-4 inline-block me-1" />
                الأدوار
            </button>
            <button wire:click="setActiveTab('teams')"
                    class="py-2 px-4 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'teams' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <x-heroicon-o-user-group class="w-4 h-4 inline-block me-1" />
                فرق العمل
            </button>
            <button wire:click="setActiveTab('permissions')"
                    class="py-2 px-4 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'permissions' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <x-heroicon-o-key class="w-4 h-4 inline-block me-1" />
                الصلاحيات
            </button>
            <button wire:click="setActiveTab('workflows')"
                    class="py-2 px-4 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'workflows' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <x-heroicon-o-arrow-path class="w-4 h-4 inline-block me-1" />
                سير العمل
            </button>
        </nav>
    </div>

    {{-- الجدول --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
