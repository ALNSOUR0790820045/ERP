<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class TenderPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // صلاحيات العطاءات التفصيلية حسب مراحل سير العمل
        $tenderPermissions = [
            // الرصد والتسجيل
            ['code' => 'tenders.tender.view', 'action' => 'view', 'name_ar' => 'عرض العطاءات', 'name_en' => 'View Tenders'],
            ['code' => 'tenders.tender.create', 'action' => 'create', 'name_ar' => 'رصد عطاء جديد', 'name_en' => 'Create Tender'],
            ['code' => 'tenders.tender.update', 'action' => 'update', 'name_ar' => 'تعديل العطاء', 'name_en' => 'Update Tender'],
            ['code' => 'tenders.tender.delete', 'action' => 'delete', 'name_ar' => 'حذف العطاء', 'name_en' => 'Delete Tender'],
            
            // إرسال للدراسة
            ['code' => 'tenders.tender.send_for_study', 'action' => 'send_for_study', 'name_ar' => 'إرسال للدراسة', 'name_en' => 'Send for Study'],
            
            // الدراسة والقرار
            ['code' => 'tenders.study.view', 'action' => 'view', 'name_ar' => 'عرض الدراسة', 'name_en' => 'View Study'],
            ['code' => 'tenders.study.edit', 'action' => 'edit', 'name_ar' => 'تعديل الدراسة', 'name_en' => 'Edit Study'],
            ['code' => 'tenders.decision.go', 'action' => 'go', 'name_ar' => 'قرار المتابعة (GO)', 'name_en' => 'GO Decision'],
            ['code' => 'tenders.decision.no_go', 'action' => 'no_go', 'name_ar' => 'قرار عدم المتابعة (NO GO)', 'name_en' => 'NO GO Decision'],
            
            // التسعير
            ['code' => 'tenders.pricing.view', 'action' => 'view', 'name_ar' => 'عرض التسعير', 'name_en' => 'View Pricing'],
            ['code' => 'tenders.pricing.edit', 'action' => 'edit', 'name_ar' => 'تعديل التسعير', 'name_en' => 'Edit Pricing'],
            ['code' => 'tenders.pricing.approve', 'action' => 'approve', 'name_ar' => 'اعتماد التسعير', 'name_en' => 'Approve Pricing'],
            
            // التجهيز والتقديم
            ['code' => 'tenders.preparation.view', 'action' => 'view', 'name_ar' => 'عرض التجهيز', 'name_en' => 'View Preparation'],
            ['code' => 'tenders.preparation.edit', 'action' => 'edit', 'name_ar' => 'تعديل التجهيز', 'name_en' => 'Edit Preparation'],
            ['code' => 'tenders.submission.submit', 'action' => 'submit', 'name_ar' => 'تقديم العطاء', 'name_en' => 'Submit Tender'],
            
            // الفتح والنتيجة
            ['code' => 'tenders.opening.view', 'action' => 'view', 'name_ar' => 'عرض الفتح', 'name_en' => 'View Opening'],
            ['code' => 'tenders.opening.edit', 'action' => 'edit', 'name_ar' => 'تسجيل نتائج الفتح', 'name_en' => 'Edit Opening Results'],
            ['code' => 'tenders.result.set', 'action' => 'set', 'name_ar' => 'تحديد النتيجة النهائية', 'name_en' => 'Set Final Result'],
            
            // التقارير والتصدير
            ['code' => 'tenders.tender.export', 'action' => 'export', 'name_ar' => 'تصدير العطاءات', 'name_en' => 'Export Tenders'],
            ['code' => 'tenders.reports.view', 'action' => 'view', 'name_ar' => 'عرض تقارير العطاءات', 'name_en' => 'View Tender Reports'],
        ];

        foreach ($tenderPermissions as $perm) {
            Permission::firstOrCreate(
                ['code' => $perm['code']],
                [
                    'module' => 'tenders',
                    'resource' => explode('.', $perm['code'])[1],
                    'action' => $perm['action'],
                    'name_ar' => $perm['name_ar'],
                    'name_en' => $perm['name_en'],
                ]
            );
        }

        // إنشاء أدوار خاصة بالعطاءات
        $tenderRoles = [
            [
                'code' => 'tender_monitor',
                'name_ar' => 'راصد عطاءات',
                'name_en' => 'Tender Monitor',
                'description' => 'يمكنه رصد وتسجيل العطاءات الجديدة',
                'level' => 20,
                'permissions' => [
                    'tenders.tender.view',
                    'tenders.tender.create',
                    'tenders.tender.update',
                    'tenders.tender.send_for_study',
                ],
            ],
            [
                'code' => 'tender_analyst',
                'name_ar' => 'محلل عطاءات',
                'name_en' => 'Tender Analyst',
                'description' => 'يمكنه دراسة العطاءات وإعداد التحليلات',
                'level' => 40,
                'permissions' => [
                    'tenders.tender.view',
                    'tenders.study.view',
                    'tenders.study.edit',
                ],
            ],
            [
                'code' => 'tender_decision_maker',
                'name_ar' => 'صاحب قرار العطاءات',
                'name_en' => 'Tender Decision Maker',
                'description' => 'يمكنه اتخاذ قرار GO/NO GO',
                'level' => 60,
                'permissions' => [
                    'tenders.tender.view',
                    'tenders.study.view',
                    'tenders.decision.go',
                    'tenders.decision.no_go',
                ],
            ],
            [
                'code' => 'tender_pricer',
                'name_ar' => 'مسعّر عطاءات',
                'name_en' => 'Tender Pricer',
                'description' => 'يمكنه إعداد تسعير العطاءات',
                'level' => 50,
                'permissions' => [
                    'tenders.tender.view',
                    'tenders.pricing.view',
                    'tenders.pricing.edit',
                ],
            ],
            [
                'code' => 'tender_pricing_approver',
                'name_ar' => 'معتمد تسعير العطاءات',
                'name_en' => 'Tender Pricing Approver',
                'description' => 'يمكنه اعتماد تسعير العطاءات',
                'level' => 70,
                'permissions' => [
                    'tenders.tender.view',
                    'tenders.pricing.view',
                    'tenders.pricing.approve',
                ],
            ],
            [
                'code' => 'tender_submitter',
                'name_ar' => 'مقدم عطاءات',
                'name_en' => 'Tender Submitter',
                'description' => 'يمكنه تجهيز وتقديم العطاءات',
                'level' => 40,
                'permissions' => [
                    'tenders.tender.view',
                    'tenders.preparation.view',
                    'tenders.preparation.edit',
                    'tenders.submission.submit',
                ],
            ],
            [
                'code' => 'tender_manager',
                'name_ar' => 'مدير عطاءات',
                'name_en' => 'Tender Manager',
                'description' => 'صلاحيات كاملة على العطاءات',
                'level' => 80,
                'permissions' => [
                    'tenders.tender.view',
                    'tenders.tender.create',
                    'tenders.tender.update',
                    'tenders.tender.delete',
                    'tenders.tender.send_for_study',
                    'tenders.study.view',
                    'tenders.study.edit',
                    'tenders.decision.go',
                    'tenders.decision.no_go',
                    'tenders.pricing.view',
                    'tenders.pricing.edit',
                    'tenders.pricing.approve',
                    'tenders.preparation.view',
                    'tenders.preparation.edit',
                    'tenders.submission.submit',
                    'tenders.opening.view',
                    'tenders.opening.edit',
                    'tenders.result.set',
                    'tenders.tender.export',
                    'tenders.reports.view',
                ],
            ],
        ];

        foreach ($tenderRoles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);
            
            $role = Role::firstOrCreate(
                ['code' => $roleData['code']],
                $roleData
            );

            // ربط الصلاحيات
            $permissionIds = Permission::whereIn('code', $permissions)->pluck('id');
            $role->permissions()->syncWithoutDetaching($permissionIds);
        }

        // منح صلاحيات العطاءات لمدير النظام ومدير الشركة
        $allTenderPermissions = Permission::where('module', 'tenders')->pluck('id');
        
        $superAdmin = Role::where('code', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($allTenderPermissions);
        }

        $companyAdmin = Role::where('code', 'company_admin')->first();
        if ($companyAdmin) {
            $companyAdmin->permissions()->syncWithoutDetaching($allTenderPermissions);
        }

        $this->command->info('✅ تم إنشاء صلاحيات وأدوار العطاءات بنجاح');
    }
}
