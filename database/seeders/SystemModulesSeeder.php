<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\SystemModule;
use App\Models\SystemScreen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemModulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * بذر الوحدات والشاشات الأساسية للنظام
     */
    public function run(): void
    {
        // الوحدات الرئيسية للنظام
        $modules = [
            [
                'code' => 'core',
                'name_ar' => 'النظام الأساسي',
                'name_en' => 'Core System',
                'description' => 'إعدادات النظام الأساسية والمستخدمين',
                'icon' => 'heroicon-o-cog-6-tooth',
                'color' => 'gray',
                'sort_order' => 1,
                'screens' => [
                    ['code' => 'dashboard', 'name_ar' => 'لوحة التحكم', 'name_en' => 'Dashboard'],
                    ['code' => 'users', 'name_ar' => 'المستخدمين', 'name_en' => 'Users'],
                    ['code' => 'roles', 'name_ar' => 'الأدوار الوظيفية', 'name_en' => 'Roles'],
                    ['code' => 'settings', 'name_ar' => 'الإعدادات', 'name_en' => 'Settings'],
                    ['code' => 'companies', 'name_ar' => 'الشركات', 'name_en' => 'Companies'],
                    ['code' => 'branches', 'name_ar' => 'الفروع', 'name_en' => 'Branches'],
                ],
            ],
            [
                'code' => 'tenders',
                'name_ar' => 'إدارة العطاءات',
                'name_en' => 'Tender Management',
                'description' => 'إدارة العطاءات والمناقصات والدراسات',
                'icon' => 'heroicon-o-document-text',
                'color' => 'success',
                'sort_order' => 2,
                'screens' => [
                    ['code' => 'tenders', 'name_ar' => 'العطاءات', 'name_en' => 'Tenders'],
                    ['code' => 'tender_items', 'name_ar' => 'بنود العطاء', 'name_en' => 'Tender Items'],
                    ['code' => 'tender_documents', 'name_ar' => 'وثائق العطاء', 'name_en' => 'Tender Documents'],
                    ['code' => 'bid_bonds', 'name_ar' => 'كفالات العطاء', 'name_en' => 'Bid Bonds'],
                    ['code' => 'tender_studies', 'name_ar' => 'دراسات العطاء', 'name_en' => 'Tender Studies'],
                    ['code' => 'tender_pricing', 'name_ar' => 'تسعير العطاء', 'name_en' => 'Tender Pricing'],
                ],
            ],
            [
                'code' => 'contracts',
                'name_ar' => 'إدارة العقود',
                'name_en' => 'Contract Management',
                'description' => 'إدارة العقود والملاحق والضمانات',
                'icon' => 'heroicon-o-document-check',
                'color' => 'warning',
                'sort_order' => 3,
                'screens' => [
                    ['code' => 'contracts', 'name_ar' => 'العقود', 'name_en' => 'Contracts'],
                    ['code' => 'contract_amendments', 'name_ar' => 'ملاحق العقود', 'name_en' => 'Contract Amendments'],
                    ['code' => 'contract_bonds', 'name_ar' => 'كفالات العقود', 'name_en' => 'Contract Bonds'],
                    ['code' => 'contract_payments', 'name_ar' => 'دفعات العقود', 'name_en' => 'Contract Payments'],
                ],
            ],
            [
                'code' => 'projects',
                'name_ar' => 'إدارة المشاريع',
                'name_en' => 'Project Management',
                'description' => 'إدارة المشاريع والأنشطة والموارد',
                'icon' => 'heroicon-o-building-office',
                'color' => 'info',
                'sort_order' => 4,
                'screens' => [
                    ['code' => 'projects', 'name_ar' => 'المشاريع', 'name_en' => 'Projects'],
                    ['code' => 'project_activities', 'name_ar' => 'أنشطة المشروع', 'name_en' => 'Project Activities'],
                    ['code' => 'work_breakdown', 'name_ar' => 'هيكل العمل', 'name_en' => 'Work Breakdown Structure'],
                    ['code' => 'daily_reports', 'name_ar' => 'التقارير اليومية', 'name_en' => 'Daily Reports'],
                    ['code' => 'project_resources', 'name_ar' => 'موارد المشروع', 'name_en' => 'Project Resources'],
                ],
            ],
            [
                'code' => 'finance',
                'name_ar' => 'الإدارة المالية',
                'name_en' => 'Financial Management',
                'description' => 'إدارة الحسابات والفواتير والمدفوعات',
                'icon' => 'heroicon-o-banknotes',
                'color' => 'danger',
                'sort_order' => 5,
                'screens' => [
                    ['code' => 'chart_of_accounts', 'name_ar' => 'دليل الحسابات', 'name_en' => 'Chart of Accounts'],
                    ['code' => 'journal_entries', 'name_ar' => 'القيود اليومية', 'name_en' => 'Journal Entries'],
                    ['code' => 'invoices', 'name_ar' => 'الفواتير', 'name_en' => 'Invoices'],
                    ['code' => 'payments', 'name_ar' => 'المدفوعات', 'name_en' => 'Payments'],
                    ['code' => 'receipts', 'name_ar' => 'المقبوضات', 'name_en' => 'Receipts'],
                    ['code' => 'bank_accounts', 'name_ar' => 'الحسابات البنكية', 'name_en' => 'Bank Accounts'],
                    ['code' => 'financial_reports', 'name_ar' => 'التقارير المالية', 'name_en' => 'Financial Reports'],
                ],
            ],
            [
                'code' => 'hr',
                'name_ar' => 'الموارد البشرية',
                'name_en' => 'Human Resources',
                'description' => 'إدارة الموظفين والرواتب والحضور',
                'icon' => 'heroicon-o-users',
                'color' => 'purple',
                'sort_order' => 6,
                'screens' => [
                    ['code' => 'employees', 'name_ar' => 'الموظفين', 'name_en' => 'Employees'],
                    ['code' => 'attendance', 'name_ar' => 'الحضور والانصراف', 'name_en' => 'Attendance'],
                    ['code' => 'payroll', 'name_ar' => 'الرواتب', 'name_en' => 'Payroll'],
                    ['code' => 'leaves', 'name_ar' => 'الإجازات', 'name_en' => 'Leaves'],
                    ['code' => 'departments', 'name_ar' => 'الأقسام', 'name_en' => 'Departments'],
                ],
            ],
            [
                'code' => 'inventory',
                'name_ar' => 'إدارة المخزون',
                'name_en' => 'Inventory Management',
                'description' => 'إدارة المستودعات والأصناف والحركات',
                'icon' => 'heroicon-o-cube',
                'color' => 'orange',
                'sort_order' => 7,
                'screens' => [
                    ['code' => 'warehouses', 'name_ar' => 'المستودعات', 'name_en' => 'Warehouses'],
                    ['code' => 'items', 'name_ar' => 'الأصناف', 'name_en' => 'Items'],
                    ['code' => 'stock_movements', 'name_ar' => 'حركات المخزون', 'name_en' => 'Stock Movements'],
                    ['code' => 'stock_take', 'name_ar' => 'الجرد', 'name_en' => 'Stock Take'],
                ],
            ],
            [
                'code' => 'procurement',
                'name_ar' => 'المشتريات',
                'name_en' => 'Procurement',
                'description' => 'إدارة طلبات الشراء والموردين',
                'icon' => 'heroicon-o-shopping-cart',
                'color' => 'cyan',
                'sort_order' => 8,
                'screens' => [
                    ['code' => 'purchase_requests', 'name_ar' => 'طلبات الشراء', 'name_en' => 'Purchase Requests'],
                    ['code' => 'purchase_orders', 'name_ar' => 'أوامر الشراء', 'name_en' => 'Purchase Orders'],
                    ['code' => 'suppliers', 'name_ar' => 'الموردين', 'name_en' => 'Suppliers'],
                    ['code' => 'goods_receipts', 'name_ar' => 'استلام البضائع', 'name_en' => 'Goods Receipts'],
                ],
            ],
        ];

        DB::transaction(function () use ($modules) {
            $sortOrder = 1;
            foreach ($modules as $moduleData) {
                $screens = $moduleData['screens'] ?? [];
                unset($moduleData['screens']);

                $module = SystemModule::updateOrCreate(
                    ['code' => $moduleData['code']],
                    $moduleData
                );

                $screenSort = 1;
                foreach ($screens as $screenData) {
                    SystemScreen::updateOrCreate(
                        [
                            'module_id' => $module->id,
                            'code' => $screenData['code'],
                        ],
                        [
                            'name_ar' => $screenData['name_ar'],
                            'name_en' => $screenData['name_en'] ?? null,
                            'sort_order' => $screenSort++,
                            'is_active' => true,
                        ]
                    );
                }
            }

            // منح مدير النظام صلاحية كاملة على جميع الوحدات
            $superAdmin = Role::where('code', 'super_admin')->first();
            if ($superAdmin) {
                $allModules = SystemModule::all();
                $moduleSync = [];
                foreach ($allModules as $module) {
                    $moduleSync[$module->id] = ['full_access' => true];
                }
                $superAdmin->systemModules()->sync($moduleSync);

                // منح صلاحية كاملة على جميع الشاشات
                $allScreens = SystemScreen::all();
                $screenSync = [];
                foreach ($allScreens as $screen) {
                    $screenSync[$screen->id] = [
                        'can_view' => true,
                        'can_create' => true,
                        'can_edit' => true,
                        'can_delete' => true,
                        'can_export' => true,
                        'can_print' => true,
                    ];
                }
                $superAdmin->systemScreens()->sync($screenSync);
            }
        });

        $this->command->info('✅ تم إنشاء الوحدات والشاشات بنجاح');
        $this->command->info('📊 عدد الوحدات: ' . SystemModule::count());
        $this->command->info('📋 عدد الشاشات: ' . SystemScreen::count());
    }
}
