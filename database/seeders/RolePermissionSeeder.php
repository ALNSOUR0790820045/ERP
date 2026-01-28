<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء الأدوار
        $roles = [
            ['code' => 'super_admin', 'name_ar' => 'مدير النظام', 'name_en' => 'Super Admin', 'is_system' => true, 'level' => 100],
            ['code' => 'company_admin', 'name_ar' => 'مدير الشركة', 'name_en' => 'Company Admin', 'is_system' => true, 'level' => 90],
            ['code' => 'project_manager', 'name_ar' => 'مدير مشاريع', 'name_en' => 'Project Manager', 'is_system' => false, 'level' => 70],
            ['code' => 'financial_manager', 'name_ar' => 'مدير مالي', 'name_en' => 'Financial Manager', 'is_system' => false, 'level' => 70],
            ['code' => 'site_engineer', 'name_ar' => 'مهندس موقع', 'name_en' => 'Site Engineer', 'is_system' => false, 'level' => 50],
            ['code' => 'accountant', 'name_ar' => 'محاسب', 'name_en' => 'Accountant', 'is_system' => false, 'level' => 40],
            ['code' => 'warehouse_keeper', 'name_ar' => 'أمين مستودع', 'name_en' => 'Warehouse Keeper', 'is_system' => false, 'level' => 30],
            ['code' => 'procurement_officer', 'name_ar' => 'موظف مشتريات', 'name_en' => 'Procurement Officer', 'is_system' => false, 'level' => 30],
            ['code' => 'hr_officer', 'name_ar' => 'موظف موارد بشرية', 'name_en' => 'HR Officer', 'is_system' => false, 'level' => 30],
            ['code' => 'standard_user', 'name_ar' => 'مستخدم عادي', 'name_en' => 'Standard User', 'is_system' => false, 'level' => 10],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['code' => $role['code']], $role);
        }

        // الموارد والصلاحيات
        $resources = [
            // النظام الأساسي
            ['module' => 'core', 'resource' => 'companies', 'name_ar' => 'الشركات'],
            ['module' => 'core', 'resource' => 'branches', 'name_ar' => 'الفروع'],
            ['module' => 'core', 'resource' => 'users', 'name_ar' => 'المستخدمين'],
            ['module' => 'core', 'resource' => 'roles', 'name_ar' => 'الأدوار'],
            ['module' => 'core', 'resource' => 'settings', 'name_ar' => 'الإعدادات'],
            
            // العطاءات
            ['module' => 'tenders', 'resource' => 'tenders', 'name_ar' => 'العطاءات'],
            ['module' => 'tenders', 'resource' => 'tender_pricing', 'name_ar' => 'تسعير العطاءات'],
            
            // العقود
            ['module' => 'contracts', 'resource' => 'contracts', 'name_ar' => 'العقود'],
            ['module' => 'contracts', 'resource' => 'contract_amendments', 'name_ar' => 'ملاحق العقود'],
            
            // المشاريع
            ['module' => 'projects', 'resource' => 'projects', 'name_ar' => 'المشاريع'],
            ['module' => 'projects', 'resource' => 'work_orders', 'name_ar' => 'أوامر العمل'],
            
            // المالية
            ['module' => 'finance', 'resource' => 'invoices', 'name_ar' => 'الفواتير'],
            ['module' => 'finance', 'resource' => 'payments', 'name_ar' => 'المدفوعات'],
            ['module' => 'finance', 'resource' => 'journal_entries', 'name_ar' => 'القيود اليومية'],
            
            // المشتريات
            ['module' => 'procurement', 'resource' => 'purchase_requests', 'name_ar' => 'طلبات الشراء'],
            ['module' => 'procurement', 'resource' => 'purchase_orders', 'name_ar' => 'أوامر الشراء'],
            
            // المستودعات
            ['module' => 'warehouse', 'resource' => 'items', 'name_ar' => 'الأصناف'],
            ['module' => 'warehouse', 'resource' => 'stock_movements', 'name_ar' => 'حركات المخزون'],
            
            // الموارد البشرية
            ['module' => 'hr', 'resource' => 'employees', 'name_ar' => 'الموظفين'],
            ['module' => 'hr', 'resource' => 'attendance', 'name_ar' => 'الحضور والانصراف'],
            ['module' => 'hr', 'resource' => 'payroll', 'name_ar' => 'الرواتب'],
        ];

        $actions = ['view', 'create', 'update', 'delete', 'export'];
        $actionLabels = [
            'view' => 'عرض',
            'create' => 'إضافة',
            'update' => 'تعديل',
            'delete' => 'حذف',
            'export' => 'تصدير',
        ];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(
                    ['code' => "{$resource['module']}.{$resource['resource']}.{$action}"],
                    [
                        'module' => $resource['module'],
                        'resource' => $resource['resource'],
                        'action' => $action,
                        'name_ar' => "{$actionLabels[$action]} {$resource['name_ar']}",
                        'name_en' => ucfirst($action) . ' ' . ucfirst(str_replace('_', ' ', $resource['resource'])),
                    ]
                );
            }
        }

        // منح صلاحيات كاملة لمدير النظام
        $superAdmin = Role::where('code', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->sync(Permission::pluck('id'));
        }

        // منح صلاحيات مدير الشركة
        $companyAdmin = Role::where('code', 'company_admin')->first();
        if ($companyAdmin) {
            $permissions = Permission::whereNotIn('resource', ['companies'])->pluck('id');
            $companyAdmin->permissions()->sync($permissions);
        }
    }
}
