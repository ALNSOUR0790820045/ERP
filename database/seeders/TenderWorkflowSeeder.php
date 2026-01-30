<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Team;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;

class TenderWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // الحصول على الأدوار والفرق
        $roles = Role::pluck('id', 'code');
        $teams = Team::pluck('id', 'code');

        // تعريف سير عمل العطاءات
        $workflow = WorkflowDefinition::firstOrCreate(
            ['code' => 'tender_workflow'],
            [
                'name' => 'سير عمل العطاءات',
                'description' => 'سير عمل متكامل لإدارة العطاءات من الرصد حتى النتيجة',
                'entity_type' => 'App\\Models\\Tender',
                'trigger_event' => 'submitted',
                'conditions' => null,
                'is_active' => true,
            ]
        );

        // حذف الخطوات القديمة إن وجدت
        $workflow->steps()->delete();

        // خطوات سير العمل - باستخدام نظام التعيين الجديد
        $steps = [
            [
                'step_order' => 1,
                'name' => 'الرصد والتسجيل',
                'description' => 'رصد العطاء وتسجيل البيانات الأساسية',
                'step_type' => 'action',
                // نظام التعيين الجديد
                'assignment_type' => 'role',
                'assigned_role_id' => $roles['tender_monitor'] ?? null,
                // الحقول القديمة للتوافق
                'approver_type' => 'role',
                'approver_role' => 'tender_monitor',
                'approval_type' => 'single',
                'required_permission' => 'tenders.tender.send_for_study',
                'time_limit_hours' => 24,
                'allow_delegation' => true,
                'notify_on_assignment' => true,
                'escalation_hours' => 24,
                'escalate_to_role_id' => $roles['tender_manager'] ?? null,
                'is_final' => false,
            ],
            [
                'step_order' => 2,
                'name' => 'الدراسة والتحليل',
                'description' => 'دراسة العطاء وتحليل المخاطر والفرص',
                'step_type' => 'review',
                'assignment_type' => 'team',
                'assigned_team_id' => $teams['tender_analysis'] ?? null,
                'approver_type' => 'role',
                'approver_role' => 'tender_analyst',
                'approval_type' => 'single',
                'required_permission' => 'tenders.study.edit',
                'time_limit_hours' => 72,
                'allow_delegation' => true,
                'notify_on_assignment' => true,
                'escalation_hours' => 48,
                'escalate_to_role_id' => $roles['tender_manager'] ?? null,
                'is_final' => false,
            ],
            [
                'step_order' => 3,
                'name' => 'قرار GO/NO GO',
                'description' => 'اتخاذ قرار المتابعة أو عدم المتابعة',
                'step_type' => 'approval',
                'assignment_type' => 'role',
                'assigned_role_id' => $roles['tender_decision_maker'] ?? null,
                'approver_type' => 'role',
                'approver_role' => 'tender_decision_maker',
                'approval_type' => 'single',
                'required_permission' => 'tenders.decision.go',
                'time_limit_hours' => 48,
                'allow_delegation' => true,
                'notify_on_assignment' => true,
                'is_final' => false,
            ],
            [
                'step_order' => 4,
                'name' => 'التسعير',
                'description' => 'إعداد تسعير العطاء',
                'step_type' => 'action',
                'assignment_type' => 'team',
                'assigned_team_id' => $teams['pricing_buildings'] ?? null, // يمكن تغييره حسب نوع المشروع
                'approver_type' => 'role',
                'approver_role' => 'tender_pricer',
                'approval_type' => 'single',
                'required_permission' => 'tenders.pricing.edit',
                'time_limit_hours' => 120,
                'allow_delegation' => true,
                'notify_on_assignment' => true,
                'is_final' => false,
            ],
            [
                'step_order' => 5,
                'name' => 'اعتماد التسعير',
                'description' => 'مراجعة واعتماد التسعير النهائي',
                'step_type' => 'approval',
                'assignment_type' => 'dynamic',
                'dynamic_assignment' => 'branch_manager', // يعتمد من مدير الفرع
                'approver_type' => 'role',
                'approver_role' => 'tender_pricing_approver',
                'approval_type' => 'single',
                'required_permission' => 'tenders.pricing.approve',
                'time_limit_hours' => 24,
                'allow_delegation' => true,
                'notify_on_assignment' => true,
                'is_final' => false,
            ],
            [
                'step_order' => 6,
                'name' => 'التجهيز والتقديم',
                'description' => 'تجهيز مستندات العطاء وتقديمه',
                'step_type' => 'action',
                'assignment_type' => 'team',
                'assigned_team_id' => $teams['tender_submission'] ?? null,
                'approver_type' => 'role',
                'approver_role' => 'tender_submitter',
                'approval_type' => 'single',
                'required_permission' => 'tenders.submission.submit',
                'time_limit_hours' => 48,
                'allow_delegation' => true,
                'notify_on_assignment' => true,
                'is_final' => false,
            ],
            [
                'step_order' => 7,
                'name' => 'فتح العروض',
                'description' => 'حضور جلسة فتح العروض وتسجيل النتائج',
                'step_type' => 'action',
                'assignment_type' => 'role',
                'assigned_role_id' => $roles['tender_submitter'] ?? null,
                'approver_type' => 'role',
                'approver_role' => 'tender_submitter',
                'approval_type' => 'single',
                'required_permission' => 'tenders.opening.edit',
                'time_limit_hours' => null,
                'allow_delegation' => false,
                'notify_on_assignment' => true,
                'is_final' => false,
            ],
            [
                'step_order' => 8,
                'name' => 'النتيجة النهائية',
                'description' => 'تسجيل النتيجة النهائية للعطاء',
                'step_type' => 'approval',
                'assignment_type' => 'role',
                'assigned_role_id' => $roles['tender_manager'] ?? null,
                'approver_type' => 'role',
                'approver_role' => 'tender_manager',
                'approval_type' => 'single',
                'required_permission' => 'tenders.result.set',
                'time_limit_hours' => null,
                'allow_delegation' => false,
                'notify_on_assignment' => true,
                'is_final' => true,
            ],
        ];

        foreach ($steps as $stepData) {
            WorkflowStep::create(array_merge($stepData, [
                'workflow_definition_id' => $workflow->id,
            ]));
        }

        $this->command->info('✅ تم إنشاء سير عمل العطاءات بنجاح مع نظام التعيين الجديد');
    }
}
