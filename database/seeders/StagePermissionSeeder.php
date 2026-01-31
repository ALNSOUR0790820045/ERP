<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleResource;
use App\Models\ModuleStage;
use App\Models\PermissionTemplate;
use App\Models\PermissionType;
use Illuminate\Database\Seeder;

class StagePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء أنواع الصلاحيات
        $this->createPermissionTypes();

        // إنشاء الوحدات
        $this->createModules();

        // إنشاء القوالب
        $this->createTemplates();
    }

    protected function createPermissionTypes(): void
    {
        $types = [
            // صلاحيات أساسية
            // صلاحيات أساسية
            [
                'code' => 'view',
                'name_ar' => 'عرض',
                'name_en' => 'View',
                'icon' => 'heroicon-o-eye',
                'category' => 'basic',
                'sort_order' => 1,
            ],
            [
                'code' => 'create',
                'name_ar' => 'إنشاء',
                'name_en' => 'Create',
                'icon' => 'heroicon-o-plus-circle',
                'category' => 'basic',
                'sort_order' => 2,
            ],
            [
                'code' => 'update',
                'name_ar' => 'تعديل',
                'name_en' => 'Update',
                'icon' => 'heroicon-o-pencil-square',
                'category' => 'basic',
                'sort_order' => 3,
            ],
            [
                'code' => 'delete',
                'name_ar' => 'حذف',
                'name_en' => 'Delete',
                'icon' => 'heroicon-o-trash',
                'category' => 'basic',
                'sort_order' => 4,
            ],
            // صلاحيات سير العمل
            [
                'code' => 'approve',
                'name_ar' => 'موافقة',
                'name_en' => 'Approve',
                'icon' => 'heroicon-o-check-circle',
                'category' => 'workflow',
                'sort_order' => 5,
            ],
            [
                'code' => 'reject',
                'name_ar' => 'رفض',
                'name_en' => 'Reject',
                'icon' => 'heroicon-o-x-circle',
                'category' => 'workflow',
                'sort_order' => 6,
            ],
            [
                'code' => 'escalate',
                'name_ar' => 'تصعيد',
                'name_en' => 'Escalate',
                'icon' => 'heroicon-o-arrow-up-circle',
                'category' => 'workflow',
                'sort_order' => 7,
            ],
            [
                'code' => 'delegate',
                'name_ar' => 'تفويض',
                'name_en' => 'Delegate',
                'icon' => 'heroicon-o-user-plus',
                'category' => 'workflow',
                'sort_order' => 8,
            ],
            [
                'code' => 'return',
                'name_ar' => 'إرجاع',
                'name_en' => 'Return',
                'icon' => 'heroicon-o-arrow-uturn-left',
                'category' => 'workflow',
                'sort_order' => 9,
            ],
            // صلاحيات التصدير
            [
                'code' => 'print',
                'name_ar' => 'طباعة',
                'name_en' => 'Print',
                'icon' => 'heroicon-o-printer',
                'category' => 'reports',
                'sort_order' => 10,
            ],
            [
                'code' => 'export',
                'name_ar' => 'تصدير',
                'name_en' => 'Export',
                'icon' => 'heroicon-o-arrow-down-tray',
                'category' => 'reports',
                'sort_order' => 11,
            ],
        ];

        foreach ($types as $type) {
            PermissionType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }

    protected function createModules(): void
    {
        $modules = [
            [
                'code' => 'tenders',
                'name_ar' => 'العطاءات',
                'name_en' => 'Tenders',
                'icon' => 'heroicon-o-document-text',
                'color' => 'primary',
                'sort_order' => 1,
                'is_active' => true,
                'stages' => [
                    [
                        'code' => 'monitoring',
                        'name_ar' => 'الرصد',
                        'name_en' => 'Monitoring',
                        'description' => 'مرحلة رصد وإدخال العطاءات الجديدة',
                        'color' => '#3B82F6',
                        'icon' => 'heroicon-o-eye',
                        'sort_order' => 1,
                        'is_initial' => true,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'study',
                        'name_ar' => 'الدراسة',
                        'name_en' => 'Study',
                        'description' => 'مرحلة دراسة وتحليل العطاء',
                        'color' => '#8B5CF6',
                        'icon' => 'heroicon-o-academic-cap',
                        'sort_order' => 2,
                        'is_initial' => false,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'decision',
                        'name_ar' => 'القرار',
                        'name_en' => 'Decision',
                        'description' => 'مرحلة اتخاذ القرار',
                        'color' => '#F59E0B',
                        'icon' => 'heroicon-o-scale',
                        'sort_order' => 3,
                        'is_initial' => false,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'purchase',
                        'name_ar' => 'الشراء',
                        'name_en' => 'Purchase',
                        'description' => 'مرحلة شراء وثائق العطاء',
                        'color' => '#10B981',
                        'icon' => 'heroicon-o-shopping-cart',
                        'sort_order' => 4,
                        'is_initial' => false,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'pricing',
                        'name_ar' => 'التسعير',
                        'name_en' => 'Pricing',
                        'description' => 'مرحلة تسعير العطاء',
                        'color' => '#EC4899',
                        'icon' => 'heroicon-o-calculator',
                        'sort_order' => 5,
                        'is_initial' => false,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'submission',
                        'name_ar' => 'التقديم',
                        'name_en' => 'Submission',
                        'description' => 'مرحلة تقديم العطاء',
                        'color' => '#6366F1',
                        'icon' => 'heroicon-o-paper-airplane',
                        'sort_order' => 6,
                        'is_initial' => false,
                        'is_final' => true,
                    ],
                ],
                'resources' => [
                    ['code' => 'tenders', 'name_ar' => 'العطاءات', 'name_en' => 'Tenders', 'filament_resource' => 'TenderResource', 'is_main' => true, 'sort_order' => 1],
                    ['code' => 'tender_documents', 'name_ar' => 'وثائق العطاءات', 'name_en' => 'Tender Documents', 'filament_resource' => 'TenderDocumentResource', 'is_main' => false, 'sort_order' => 2],
                    ['code' => 'bid_bonds', 'name_ar' => 'كفالات العطاءات', 'name_en' => 'Bid Bonds', 'filament_resource' => 'BidBondResource', 'is_main' => false, 'sort_order' => 3],
                ],
            ],
            [
                'code' => 'contracts',
                'name_ar' => 'العقود',
                'name_en' => 'Contracts',
                'icon' => 'heroicon-o-document-duplicate',
                'color' => 'success',
                'sort_order' => 2,
                'is_active' => true,
                'stages' => [
                    [
                        'code' => 'draft',
                        'name_ar' => 'مسودة',
                        'name_en' => 'Draft',
                        'description' => 'مرحلة إعداد العقد',
                        'color' => '#9CA3AF',
                        'icon' => 'heroicon-o-pencil',
                        'sort_order' => 1,
                        'is_initial' => true,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'review',
                        'name_ar' => 'المراجعة',
                        'name_en' => 'Review',
                        'description' => 'مرحلة مراجعة العقد',
                        'color' => '#3B82F6',
                        'icon' => 'heroicon-o-document-magnifying-glass',
                        'sort_order' => 2,
                        'is_initial' => false,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'approval',
                        'name_ar' => 'الموافقة',
                        'name_en' => 'Approval',
                        'description' => 'مرحلة الموافقة على العقد',
                        'color' => '#F59E0B',
                        'icon' => 'heroicon-o-check-badge',
                        'sort_order' => 3,
                        'is_initial' => false,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'active',
                        'name_ar' => 'نشط',
                        'name_en' => 'Active',
                        'description' => 'العقد نشط وقيد التنفيذ',
                        'color' => '#10B981',
                        'icon' => 'heroicon-o-play',
                        'sort_order' => 4,
                        'is_initial' => false,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'closed',
                        'name_ar' => 'مغلق',
                        'name_en' => 'Closed',
                        'description' => 'العقد منتهي',
                        'color' => '#6B7280',
                        'icon' => 'heroicon-o-lock-closed',
                        'sort_order' => 5,
                        'is_initial' => false,
                        'is_final' => true,
                    ],
                ],
                'resources' => [
                    ['code' => 'contracts', 'name_ar' => 'العقود', 'name_en' => 'Contracts', 'filament_resource' => 'ContractResource', 'is_main' => true, 'sort_order' => 1],
                ],
            ],
            [
                'code' => 'projects',
                'name_ar' => 'المشاريع',
                'name_en' => 'Projects',
                'icon' => 'heroicon-o-building-office-2',
                'color' => 'warning',
                'sort_order' => 3,
                'is_active' => true,
                'stages' => [
                    [
                        'code' => 'planning',
                        'name_ar' => 'التخطيط',
                        'name_en' => 'Planning',
                        'description' => 'مرحلة التخطيط للمشروع',
                        'color' => '#3B82F6',
                        'icon' => 'heroicon-o-map',
                        'sort_order' => 1,
                        'is_initial' => true,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'execution',
                        'name_ar' => 'التنفيذ',
                        'name_en' => 'Execution',
                        'description' => 'مرحلة تنفيذ المشروع',
                        'color' => '#F59E0B',
                        'icon' => 'heroicon-o-cog-6-tooth',
                        'sort_order' => 2,
                        'is_initial' => false,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'handover',
                        'name_ar' => 'التسليم',
                        'name_en' => 'Handover',
                        'description' => 'مرحلة تسليم المشروع',
                        'color' => '#10B981',
                        'icon' => 'heroicon-o-gift',
                        'sort_order' => 3,
                        'is_initial' => false,
                        'is_final' => true,
                    ],
                ],
                'resources' => [
                    ['code' => 'projects', 'name_ar' => 'المشاريع', 'name_en' => 'Projects', 'filament_resource' => 'ProjectResource', 'is_main' => true, 'sort_order' => 1],
                ],
            ],
            [
                'code' => 'finance',
                'name_ar' => 'المالية',
                'name_en' => 'Finance',
                'icon' => 'heroicon-o-banknotes',
                'color' => 'info',
                'sort_order' => 4,
                'is_active' => true,
                'stages' => [
                    [
                        'code' => 'entry',
                        'name_ar' => 'الإدخال',
                        'name_en' => 'Entry',
                        'description' => 'مرحلة إدخال القيود',
                        'color' => '#3B82F6',
                        'icon' => 'heroicon-o-pencil',
                        'sort_order' => 1,
                        'is_initial' => true,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'verification',
                        'name_ar' => 'التدقيق',
                        'name_en' => 'Verification',
                        'description' => 'مرحلة تدقيق القيود',
                        'color' => '#F59E0B',
                        'icon' => 'heroicon-o-check',
                        'sort_order' => 2,
                        'is_initial' => false,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'posted',
                        'name_ar' => 'مرحّل',
                        'name_en' => 'Posted',
                        'description' => 'القيد مرحّل',
                        'color' => '#10B981',
                        'icon' => 'heroicon-o-check-circle',
                        'sort_order' => 3,
                        'is_initial' => false,
                        'is_final' => true,
                    ],
                ],
                'resources' => [
                    ['code' => 'journal_entries', 'name_ar' => 'قيود اليومية', 'name_en' => 'Journal Entries', 'filament_resource' => 'JournalEntryResource', 'is_main' => true, 'sort_order' => 1],
                ],
            ],
            [
                'code' => 'hr',
                'name_ar' => 'الموارد البشرية',
                'name_en' => 'Human Resources',
                'icon' => 'heroicon-o-users',
                'color' => 'purple',
                'sort_order' => 5,
                'is_active' => true,
                'stages' => [
                    [
                        'code' => 'request',
                        'name_ar' => 'الطلب',
                        'name_en' => 'Request',
                        'description' => 'مرحلة تقديم الطلب',
                        'color' => '#3B82F6',
                        'icon' => 'heroicon-o-document-plus',
                        'sort_order' => 1,
                        'is_initial' => true,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'manager_approval',
                        'name_ar' => 'موافقة المدير',
                        'name_en' => 'Manager Approval',
                        'description' => 'مرحلة موافقة المدير المباشر',
                        'color' => '#F59E0B',
                        'icon' => 'heroicon-o-user-circle',
                        'sort_order' => 2,
                        'is_initial' => false,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'hr_approval',
                        'name_ar' => 'موافقة HR',
                        'name_en' => 'HR Approval',
                        'description' => 'مرحلة موافقة الموارد البشرية',
                        'color' => '#8B5CF6',
                        'icon' => 'heroicon-o-check-badge',
                        'sort_order' => 3,
                        'is_initial' => false,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'completed',
                        'name_ar' => 'مكتمل',
                        'name_en' => 'Completed',
                        'description' => 'الطلب مكتمل',
                        'color' => '#10B981',
                        'icon' => 'heroicon-o-check-circle',
                        'sort_order' => 4,
                        'is_initial' => false,
                        'is_final' => true,
                    ],
                ],
                'resources' => [
                    ['code' => 'employees', 'name_ar' => 'الموظفين', 'name_en' => 'Employees', 'filament_resource' => 'EmployeeResource', 'is_main' => true, 'sort_order' => 1],
                    ['code' => 'leave_requests', 'name_ar' => 'طلبات الإجازة', 'name_en' => 'Leave Requests', 'filament_resource' => 'LeaveRequestResource', 'is_main' => false, 'sort_order' => 2],
                ],
            ],
            [
                'code' => 'inventory',
                'name_ar' => 'المخزون',
                'name_en' => 'Inventory',
                'icon' => 'heroicon-o-cube',
                'color' => 'danger',
                'sort_order' => 6,
                'is_active' => true,
                'stages' => [
                    [
                        'code' => 'request',
                        'name_ar' => 'الطلب',
                        'name_en' => 'Request',
                        'description' => 'مرحلة طلب المواد',
                        'color' => '#3B82F6',
                        'icon' => 'heroicon-o-clipboard-document-list',
                        'sort_order' => 1,
                        'is_initial' => true,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'approval',
                        'name_ar' => 'الموافقة',
                        'name_en' => 'Approval',
                        'description' => 'مرحلة الموافقة على الطلب',
                        'color' => '#F59E0B',
                        'icon' => 'heroicon-o-check',
                        'sort_order' => 2,
                        'is_initial' => false,
                        'is_final' => false,
                    ],
                    [
                        'code' => 'issued',
                        'name_ar' => 'مصروف',
                        'name_en' => 'Issued',
                        'description' => 'تم صرف المواد',
                        'color' => '#10B981',
                        'icon' => 'heroicon-o-arrow-right-on-rectangle',
                        'sort_order' => 3,
                        'is_initial' => false,
                        'is_final' => true,
                    ],
                ],
                'resources' => [
                    ['code' => 'items', 'name_ar' => 'الأصناف', 'name_en' => 'Items', 'filament_resource' => 'ItemResource', 'is_main' => true, 'sort_order' => 1],
                    ['code' => 'stock_movements', 'name_ar' => 'حركات المخزون', 'name_en' => 'Stock Movements', 'filament_resource' => 'StockMovementResource', 'is_main' => false, 'sort_order' => 2],
                ],
            ],
        ];

        foreach ($modules as $moduleData) {
            $stages = $moduleData['stages'] ?? [];
            $resources = $moduleData['resources'] ?? [];
            unset($moduleData['stages'], $moduleData['resources']);

            $module = Module::updateOrCreate(
                ['code' => $moduleData['code']],
                $moduleData
            );

            // إنشاء المراحل
            foreach ($stages as $stageData) {
                ModuleStage::updateOrCreate(
                    [
                        'module_id' => $module->id,
                        'code' => $stageData['code'],
                    ],
                    $stageData
                );
            }

            // إنشاء الموارد
            foreach ($resources as $resourceData) {
                ModuleResource::updateOrCreate(
                    [
                        'module_id' => $module->id,
                        'code' => $resourceData['code'],
                    ],
                    $resourceData
                );
            }
        }
    }

    protected function createTemplates(): void
    {
        $tendersModule = Module::where('code', 'tenders')->first();
        
        if (!$tendersModule) {
            return;
        }

        $templates = PermissionTemplate::getTenderTemplates();

        foreach ($templates as $code => $template) {
            PermissionTemplate::updateOrCreate(
                [
                    'code' => $code,
                    'module_id' => $tendersModule->id,
                ],
                [
                    'name_ar' => $template['name_ar'],
                    'name_en' => $template['name_en'],
                    'description' => $template['description'],
                    'permissions' => $template['permissions'],
                    'is_active' => true,
                ]
            );
        }
    }
}
