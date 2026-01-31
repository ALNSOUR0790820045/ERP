<?php

namespace App\Console\Commands;

use App\Models\SystemModule;
use App\Models\SystemScreen;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class SyncFilamentResources extends Command
{
    protected $signature = 'erp:sync-resources {--fresh : حذف الشاشات القديمة وإعادة المسح}';
    protected $description = 'مسح وتسجيل كل Filament Resources تلقائياً في نظام الصلاحيات';

    // تصنيف الـ Resources حسب الوحدات
    protected array $moduleMapping = [
        'core' => ['User', 'Role', 'Branch', 'Company', 'Setting', 'Team', 'Permission', 'SystemModule'],
        'tenders' => ['Tender', 'TenderItem', 'TenderDocument', 'TenderBond', 'TenderStudy', 'BidComparison'],
        'contracts' => ['Contract', 'ContractAmendment', 'ContractBond', 'ContractPayment', 'Variation'],
        'projects' => ['Project', 'ProjectActivity', 'WorkBreakdown', 'DailyReport', 'Wbs', 'GanttActivity'],
        'finance' => ['ChartOfAccount', 'JournalEntry', 'Invoice', 'Payment', 'Receipt', 'BankAccount', 'Check', 'Budget'],
        'hr' => ['Employee', 'Attendance', 'Payroll', 'Leave', 'Department', 'Salary', 'Allowance', 'Deduction'],
        'inventory' => ['Warehouse', 'Item', 'StockMovement', 'StockTake', 'InventoryItem', 'BinCard'],
        'procurement' => ['PurchaseRequest', 'PurchaseOrder', 'Supplier', 'GoodsReceipt', 'Quotation'],
    ];

    // أيقونات الوحدات
    protected array $moduleIcons = [
        'core' => 'heroicon-o-cog-6-tooth',
        'tenders' => 'heroicon-o-document-text',
        'contracts' => 'heroicon-o-document-check',
        'projects' => 'heroicon-o-building-office',
        'finance' => 'heroicon-o-banknotes',
        'hr' => 'heroicon-o-users',
        'inventory' => 'heroicon-o-cube',
        'procurement' => 'heroicon-o-shopping-cart',
    ];

    // ألوان الوحدات
    protected array $moduleColors = [
        'core' => 'gray',
        'tenders' => 'success',
        'contracts' => 'warning',
        'projects' => 'info',
        'finance' => 'danger',
        'hr' => 'purple',
        'inventory' => 'orange',
        'procurement' => 'cyan',
    ];

    // أسماء الوحدات
    protected array $moduleNames = [
        'core' => ['ar' => 'النظام الأساسي', 'en' => 'Core System'],
        'tenders' => ['ar' => 'إدارة العطاءات', 'en' => 'Tender Management'],
        'contracts' => ['ar' => 'إدارة العقود', 'en' => 'Contract Management'],
        'projects' => ['ar' => 'إدارة المشاريع', 'en' => 'Project Management'],
        'finance' => ['ar' => 'الإدارة المالية', 'en' => 'Financial Management'],
        'hr' => ['ar' => 'الموارد البشرية', 'en' => 'Human Resources'],
        'inventory' => ['ar' => 'إدارة المخزون', 'en' => 'Inventory Management'],
        'procurement' => ['ar' => 'المشتريات', 'en' => 'Procurement'],
    ];

    public function handle(): int
    {
        $this->info('🔄 بدء مسح Filament Resources...');

        if ($this->option('fresh')) {
            $this->warn('⚠️ حذف الشاشات القديمة...');
            SystemScreen::truncate();
        }

        $resourcesPath = app_path('Filament/Resources');
        
        if (!File::isDirectory($resourcesPath)) {
            $this->error('❌ مجلد Resources غير موجود!');
            return 1;
        }

        $files = File::allFiles($resourcesPath);
        $resources = [];

        foreach ($files as $file) {
            $filename = $file->getFilenameWithoutExtension();
            
            // تجاهل الملفات الفرعية (Pages, RelationManagers)
            if (Str::contains($file->getPath(), ['Pages', 'RelationManagers'])) {
                continue;
            }

            if (Str::endsWith($filename, 'Resource')) {
                $resources[] = $filename;
            }
        }

        $this->info("📋 تم العثور على " . count($resources) . " Resource");

        // التأكد من وجود الوحدات الأساسية
        $this->ensureModulesExist();

        $added = 0;
        $updated = 0;
        $sortOrder = 1;

        foreach ($resources as $resourceName) {
            $modelName = Str::replaceLast('Resource', '', $resourceName);
            $moduleCode = $this->detectModule($modelName);
            
            $module = SystemModule::where('code', $moduleCode)->first();
            
            if (!$module) {
                $this->warn("⚠️ الوحدة غير موجودة: {$moduleCode}");
                continue;
            }

            $screenCode = Str::snake($modelName);
            $resourceClass = "App\\Filament\\Resources\\{$resourceName}";
            
            // محاولة الحصول على اسم الشاشة من الـ Resource
            $nameAr = $this->getResourceLabel($resourceClass) ?? $this->humanize($modelName);
            
            $screen = SystemScreen::updateOrCreate(
                [
                    'module_id' => $module->id,
                    'code' => $screenCode,
                ],
                [
                    'name_ar' => $nameAr,
                    'name_en' => $modelName,
                    'resource_class' => $resourceClass,
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                ]
            );

            if ($screen->wasRecentlyCreated) {
                $added++;
                $this->line("  ✅ <fg=green>جديد:</> {$module->name_ar} → {$nameAr}");
            } else {
                $updated++;
                $this->line("  🔄 <fg=yellow>تحديث:</> {$module->name_ar} → {$nameAr}");
            }
        }

        $this->newLine();
        $this->info("✅ تم الانتهاء!");
        $this->table(
            ['الإجراء', 'العدد'],
            [
                ['شاشات جديدة', $added],
                ['شاشات محدثة', $updated],
                ['إجمالي الوحدات', SystemModule::count()],
                ['إجمالي الشاشات', SystemScreen::count()],
            ]
        );

        return 0;
    }

    protected function ensureModulesExist(): void
    {
        $sortOrder = 1;
        foreach ($this->moduleNames as $code => $names) {
            SystemModule::updateOrCreate(
                ['code' => $code],
                [
                    'name_ar' => $names['ar'],
                    'name_en' => $names['en'],
                    'icon' => $this->moduleIcons[$code] ?? 'heroicon-o-squares-2x2',
                    'color' => $this->moduleColors[$code] ?? 'gray',
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                ]
            );
        }
    }

    protected function detectModule(string $modelName): string
    {
        foreach ($this->moduleMapping as $module => $models) {
            foreach ($models as $model) {
                if (Str::contains($modelName, $model)) {
                    return $module;
                }
            }
        }

        // محاولة التخمين من اسم الموديل
        $lowerName = Str::lower($modelName);
        
        if (Str::contains($lowerName, ['tender', 'bid', 'quotation'])) return 'tenders';
        if (Str::contains($lowerName, ['contract', 'variation', 'amendment'])) return 'contracts';
        if (Str::contains($lowerName, ['project', 'wbs', 'gantt', 'activity', 'daily'])) return 'projects';
        if (Str::contains($lowerName, ['invoice', 'payment', 'receipt', 'journal', 'account', 'bank', 'budget', 'check'])) return 'finance';
        if (Str::contains($lowerName, ['employee', 'salary', 'payroll', 'leave', 'attendance', 'department'])) return 'hr';
        if (Str::contains($lowerName, ['warehouse', 'inventory', 'stock', 'item', 'bin'])) return 'inventory';
        if (Str::contains($lowerName, ['purchase', 'supplier', 'goods', 'procurement'])) return 'procurement';

        return 'core';
    }

    protected function getResourceLabel(string $resourceClass): ?string
    {
        if (!class_exists($resourceClass)) {
            return null;
        }

        try {
            $reflection = new ReflectionClass($resourceClass);
            
            // محاولة الحصول على pluralModelLabel
            if ($reflection->hasProperty('pluralModelLabel')) {
                $prop = $reflection->getProperty('pluralModelLabel');
                $prop->setAccessible(true);
                $value = $prop->getValue();
                if ($value) return $value;
            }

            // محاولة الحصول على modelLabel
            if ($reflection->hasProperty('modelLabel')) {
                $prop = $reflection->getProperty('modelLabel');
                $prop->setAccessible(true);
                $value = $prop->getValue();
                if ($value) return $value;
            }

            // محاولة الحصول على navigationLabel
            if ($reflection->hasProperty('navigationLabel')) {
                $prop = $reflection->getProperty('navigationLabel');
                $prop->setAccessible(true);
                $value = $prop->getValue();
                if ($value) return $value;
            }
        } catch (\Exception $e) {
            // تجاهل الأخطاء
        }

        return null;
    }

    protected function humanize(string $name): string
    {
        return Str::title(Str::snake($name, ' '));
    }
}
