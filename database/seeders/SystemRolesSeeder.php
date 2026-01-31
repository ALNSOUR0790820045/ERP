<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\SystemModule;
use Illuminate\Database\Seeder;

class SystemRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. الأدوار الوظيفية (job roles)
        $jobRoles = [
            [
                'code' => 'financial_manager',
                'name_ar' => 'مدير مالي',
                'name_en' => 'Financial Manager',
                'type' => 'job',
                'description' => 'مسؤول عن إدارة الوحدة المالية',
                'icon' => 'heroicon-o-banknotes',
                'color' => 'success',
                'level' => 80,
                'modules' => ['finance'],
            ],
            [
                'code' => 'accountant',
                'name_ar' => 'محاسب',
                'name_en' => 'Accountant',
                'type' => 'job',
                'description' => 'يعمل بالوحدة المالية بصلاحيات محدودة',
                'icon' => 'heroicon-o-calculator',
                'color' => 'info',
                'level' => 50,
                'modules' => ['finance'],
            ],
            [
                'code' => 'project_manager',
                'name_ar' => 'مدير مشاريع',
                'name_en' => 'Project Manager',
                'type' => 'job',
                'description' => 'مسؤول عن إدارة المشاريع',
                'icon' => 'heroicon-o-briefcase',
                'color' => 'warning',
                'level' => 80,
                'modules' => ['projects'],
            ],
            [
                'code' => 'site_engineer',
                'name_ar' => 'مهندس موقع',
                'name_en' => 'Site Engineer',
                'type' => 'job',
                'description' => 'يعمل بوحدة المشاريع بالموقع',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'color' => 'gray',
                'level' => 40,
                'modules' => ['projects'],
            ],
            [
                'code' => 'hr_manager',
                'name_ar' => 'مدير موارد بشرية',
                'name_en' => 'HR Manager',
                'type' => 'job',
                'description' => 'مسؤول عن إدارة الموارد البشرية',
                'icon' => 'heroicon-o-user-group',
                'color' => 'primary',
                'level' => 80,
                'modules' => ['hr'],
            ],
            [
                'code' => 'warehouse_manager',
                'name_ar' => 'مدير مخازن',
                'name_en' => 'Warehouse Manager',
                'type' => 'job',
                'description' => 'مسؤول عن إدارة المخازن',
                'icon' => 'heroicon-o-cube',
                'color' => 'danger',
                'level' => 70,
                'modules' => ['inventory'],
            ],
            [
                'code' => 'procurement_manager',
                'name_ar' => 'مدير مشتريات',
                'name_en' => 'Procurement Manager',
                'type' => 'job',
                'description' => 'مسؤول عن إدارة المشتريات',
                'icon' => 'heroicon-o-shopping-cart',
                'color' => 'info',
                'level' => 70,
                'modules' => ['procurement'],
            ],
            [
                'code' => 'secretary',
                'name_ar' => 'سكرتير/ة',
                'name_en' => 'Secretary',
                'type' => 'job',
                'description' => 'أعمال السكرتارية والإدخال',
                'icon' => 'heroicon-o-clipboard-document-list',
                'color' => 'gray',
                'level' => 30,
                'modules' => ['tenders', 'contracts'],
            ],
        ];

        foreach ($jobRoles as $roleData) {
            $modules = $roleData['modules'] ?? [];
            unset($roleData['modules']);
            
            $role = Role::updateOrCreate(
                ['code' => $roleData['code']],
                array_merge($roleData, ['is_active' => true])
            );

            // ربط الوحدات بالدور
            $moduleIds = SystemModule::whereIn('code', $modules)->pluck('id')->toArray();
            if (!empty($moduleIds)) {
                $role->systemModules()->syncWithoutDetaching($moduleIds);
            }

            $this->command->info("✓ تم إنشاء دور: {$roleData['name_ar']}");
        }

        // 2. تحديث الأدوار الحالية (العطاءات) لتكون من نوع tender
        Role::whereNotIn('code', array_column($jobRoles, 'code'))
            ->where('code', '!=', 'super_admin')
            ->update(['type' => 'tender']);

        // 3. تحديث مدير النظام ليكون من نوع system
        Role::where('code', 'super_admin')->update([
            'type' => 'system',
            'icon' => 'heroicon-o-shield-check',
            'color' => 'danger',
        ]);

        $this->command->info("\n=== تم إنشاء " . count($jobRoles) . " دور وظيفي ===");
    }
}
