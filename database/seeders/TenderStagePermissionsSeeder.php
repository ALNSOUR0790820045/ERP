<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * Seeder لصلاحيات مراحل العطاءات
 * 
 * هيكل Permission:
 * - code: الكود الفريد (مثل: tenders.discovery.access)
 * - module: الوحدة (tenders)
 * - resource: المورد (discovery, study, etc.)
 * - action: الإجراء (access, edit, decide, etc.)
 * - name_ar: الاسم بالعربي
 * - name_en: الاسم بالإنجليزي
 */
class TenderStagePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // ==========================================
            // صلاحيات المرحلة 1: الرصد والتسجيل
            // ==========================================
            [
                'code' => 'tenders.discovery.access',
                'module' => 'tenders',
                'resource' => 'discovery',
                'action' => 'access',
                'name_ar' => 'الوصول لمرحلة الرصد',
                'name_en' => 'Access Discovery Stage',
            ],
            [
                'code' => 'tenders.discovery.edit',
                'module' => 'tenders',
                'resource' => 'discovery',
                'action' => 'edit',
                'name_ar' => 'تعديل بيانات الرصد',
                'name_en' => 'Edit Discovery Data',
            ],
            [
                'code' => 'tenders.discovery.edit_any_stage',
                'module' => 'tenders',
                'resource' => 'discovery',
                'action' => 'edit_any_stage',
                'name_ar' => 'تعديل الرصد في أي مرحلة',
                'name_en' => 'Edit Discovery Any Stage',
            ],

            // ==========================================
            // صلاحيات المرحلة 2: الدراسة والقرار
            // ==========================================
            [
                'code' => 'tenders.study.access',
                'module' => 'tenders',
                'resource' => 'study',
                'action' => 'access',
                'name_ar' => 'الوصول لمرحلة الدراسة',
                'name_en' => 'Access Study Stage',
            ],
            [
                'code' => 'tenders.study.edit',
                'module' => 'tenders',
                'resource' => 'study',
                'action' => 'edit',
                'name_ar' => 'تعديل بيانات الدراسة',
                'name_en' => 'Edit Study Data',
            ],
            [
                'code' => 'tenders.study.decide',
                'module' => 'tenders',
                'resource' => 'study',
                'action' => 'decide',
                'name_ar' => 'اتخاذ قرار Go/No-Go',
                'name_en' => 'Make Go/No-Go Decision',
            ],
            [
                'code' => 'tenders.study.edit_any_stage',
                'module' => 'tenders',
                'resource' => 'study',
                'action' => 'edit_any_stage',
                'name_ar' => 'تعديل الدراسة في أي مرحلة',
                'name_en' => 'Edit Study Any Stage',
            ],

            // ==========================================
            // صلاحيات المرحلة 3: التسعير وإعداد العرض
            // ==========================================
            [
                'code' => 'tenders.pricing.access',
                'module' => 'tenders',
                'resource' => 'pricing',
                'action' => 'access',
                'name_ar' => 'الوصول لمرحلة التسعير',
                'name_en' => 'Access Pricing Stage',
            ],
            [
                'code' => 'tenders.pricing.edit',
                'module' => 'tenders',
                'resource' => 'pricing',
                'action' => 'edit',
                'name_ar' => 'تعديل التسعير',
                'name_en' => 'Edit Pricing',
            ],
            [
                'code' => 'tenders.pricing.manage_boq',
                'module' => 'tenders',
                'resource' => 'pricing',
                'action' => 'manage_boq',
                'name_ar' => 'إدارة جدول الكميات',
                'name_en' => 'Manage BOQ',
            ],
            [
                'code' => 'tenders.pricing.approve',
                'module' => 'tenders',
                'resource' => 'pricing',
                'action' => 'approve',
                'name_ar' => 'اعتماد التسعير',
                'name_en' => 'Approve Pricing',
            ],
            [
                'code' => 'tenders.pricing.edit_any_stage',
                'module' => 'tenders',
                'resource' => 'pricing',
                'action' => 'edit_any_stage',
                'name_ar' => 'تعديل التسعير في أي مرحلة',
                'name_en' => 'Edit Pricing Any Stage',
            ],

            // ==========================================
            // صلاحيات المرحلة 4: التقديم
            // ==========================================
            [
                'code' => 'tenders.submission.access',
                'module' => 'tenders',
                'resource' => 'submission',
                'action' => 'access',
                'name_ar' => 'الوصول لمرحلة التقديم',
                'name_en' => 'Access Submission Stage',
            ],
            [
                'code' => 'tenders.submission.edit',
                'module' => 'tenders',
                'resource' => 'submission',
                'action' => 'edit',
                'name_ar' => 'تسجيل التقديم',
                'name_en' => 'Edit Submission',
            ],
            [
                'code' => 'tenders.submission.confirm',
                'module' => 'tenders',
                'resource' => 'submission',
                'action' => 'confirm',
                'name_ar' => 'تأكيد التقديم',
                'name_en' => 'Confirm Submission',
            ],
            [
                'code' => 'tenders.submission.edit_any_stage',
                'module' => 'tenders',
                'resource' => 'submission',
                'action' => 'edit_any_stage',
                'name_ar' => 'تعديل التقديم في أي مرحلة',
                'name_en' => 'Edit Submission Any Stage',
            ],

            // ==========================================
            // صلاحيات المرحلة 5: الفتح والنتائج
            // ==========================================
            [
                'code' => 'tenders.opening.access',
                'module' => 'tenders',
                'resource' => 'opening',
                'action' => 'access',
                'name_ar' => 'الوصول لمرحلة الفتح',
                'name_en' => 'Access Opening Stage',
            ],
            [
                'code' => 'tenders.opening.edit',
                'module' => 'tenders',
                'resource' => 'opening',
                'action' => 'edit',
                'name_ar' => 'تسجيل نتائج الفتح',
                'name_en' => 'Edit Opening Results',
            ],
            [
                'code' => 'tenders.opening.manage_competitors',
                'module' => 'tenders',
                'resource' => 'opening',
                'action' => 'manage_competitors',
                'name_ar' => 'إدارة المنافسين',
                'name_en' => 'Manage Competitors',
            ],
            [
                'code' => 'tenders.opening.edit_any_stage',
                'module' => 'tenders',
                'resource' => 'opening',
                'action' => 'edit_any_stage',
                'name_ar' => 'تعديل الفتح في أي مرحلة',
                'name_en' => 'Edit Opening Any Stage',
            ],

            // ==========================================
            // صلاحيات المرحلة 6: الترسية والنتيجة
            // ==========================================
            [
                'code' => 'tenders.award.access',
                'module' => 'tenders',
                'resource' => 'award',
                'action' => 'access',
                'name_ar' => 'الوصول لمرحلة الترسية',
                'name_en' => 'Access Award Stage',
            ],
            [
                'code' => 'tenders.award.edit',
                'module' => 'tenders',
                'resource' => 'award',
                'action' => 'edit',
                'name_ar' => 'تسجيل النتيجة',
                'name_en' => 'Edit Award Result',
            ],
            [
                'code' => 'tenders.award.convert_to_project',
                'module' => 'tenders',
                'resource' => 'award',
                'action' => 'convert_to_project',
                'name_ar' => 'تحويل لمشروع',
                'name_en' => 'Convert to Project',
            ],
            [
                'code' => 'tenders.award.edit_any_stage',
                'module' => 'tenders',
                'resource' => 'award',
                'action' => 'edit_any_stage',
                'name_ar' => 'تعديل الترسية',
                'name_en' => 'Edit Award Any Stage',
            ],

            // ==========================================
            // صلاحيات عامة للعطاءات
            // ==========================================
            [
                'code' => 'tenders.tender.view',
                'module' => 'tenders',
                'resource' => 'tender',
                'action' => 'view',
                'name_ar' => 'عرض العطاءات',
                'name_en' => 'View Tenders',
            ],
            [
                'code' => 'tenders.tender.create',
                'module' => 'tenders',
                'resource' => 'tender',
                'action' => 'create',
                'name_ar' => 'رصد عطاء جديد',
                'name_en' => 'Create Tender',
            ],
            [
                'code' => 'tenders.tender.update',
                'module' => 'tenders',
                'resource' => 'tender',
                'action' => 'update',
                'name_ar' => 'تعديل العطاءات',
                'name_en' => 'Update Tenders',
            ],
            [
                'code' => 'tenders.tender.delete',
                'module' => 'tenders',
                'resource' => 'tender',
                'action' => 'delete',
                'name_ar' => 'حذف العطاءات',
                'name_en' => 'Delete Tenders',
            ],
            [
                'code' => 'tenders.tender.export',
                'module' => 'tenders',
                'resource' => 'tender',
                'action' => 'export',
                'name_ar' => 'تصدير العطاءات',
                'name_en' => 'Export Tenders',
            ],
        ];

        $count = 0;
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['code' => $permission['code']],
                $permission
            );
            $count++;
        }

        $this->command->info("✅ تم إنشاء {$count} صلاحية لمراحل العطاءات");
    }
}
