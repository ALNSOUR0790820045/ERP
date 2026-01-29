<?php
// Script to update navigation groups for all Filament Resources

$resourcesPath = __DIR__ . '/app/Filament/Resources';
$files = glob($resourcesPath . '/*.php');

$groupMappings = [
    // العطاءات والمناقصات
    'Tender' => 'العطاءات والمناقصات',
    
    // المشاريع والعقود
    'Project' => 'المشاريع والعقود',
    'Contract' => 'المشاريع والعقود',
    'Bim' => 'المشاريع والعقود',
    'RiskAssessment' => 'المشاريع والعقود',
    'Milestone' => 'المشاريع والعقود',
    'ProgressReport' => 'المشاريع والعقود',
    'VariationOrder' => 'المشاريع والعقود',
    
    // المالية والمحاسبة
    'Budget' => 'المالية والمحاسبة',
    'ChartOfAccount' => 'المالية والمحاسبة',
    'JournalEntry' => 'المالية والمحاسبة',
    'JournalVoucher' => 'المالية والمحاسبة',
    'GeneralLedger' => 'المالية والمحاسبة',
    'Cheque' => 'المالية والمحاسبة',
    'Bank' => 'المالية والمحاسبة',
    'BankReconciliation' => 'المالية والمحاسبة',
    'BankAccount' => 'المالية والمحاسبة',
    'CostCenter' => 'المالية والمحاسبة',
    'Petty' => 'المالية والمحاسبة',
    'TaxReturn' => 'المالية والمحاسبة',
    'FixedAsset' => 'المالية والمحاسبة',
    'Invoice' => 'المالية والمحاسبة',
    'Payment' => 'المالية والمحاسبة',
    'Receipt' => 'المالية والمحاسبة',
    'CustomerReceipt' => 'المالية والمحاسبة',
    'VendorPayment' => 'المالية والمحاسبة',
    'Retention' => 'المالية والمحاسبة',
    'ProgressBilling' => 'المالية والمحاسبة',
    'SubcontractorPayment' => 'المالية والمحاسبة',
    
    // المشتريات والمخازن
    'PurchaseOrder' => 'المشتريات والمخازن',
    'PurchaseRequest' => 'المشتريات والمخازن',
    'PurchaseRequisition' => 'المشتريات والمخازن',
    'Blanket' => 'المشتريات والمخازن',
    'Vendor' => 'المشتريات والمخازن',
    'Supplier' => 'المشتريات والمخازن',
    'Inventory' => 'المشتريات والمخازن',
    'Stock' => 'المشتريات والمخازن',
    'Warehouse' => 'المشتريات والمخازن',
    'Material' => 'المشتريات والمخازن',
    'Asset' => 'المشتريات والمخازن',
    'Equipment' => 'المشتريات والمخازن',
    'GoodsReceipt' => 'المشتريات والمخازن',
    'Item' => 'المشتريات والمخازن',
    'UnitOfMeasure' => 'المشتريات والمخازن',
    
    // الموارد البشرية
    'Employee' => 'الموارد البشرية',
    'Department' => 'الموارد البشرية',
    'Attendance' => 'الموارد البشرية',
    'Leave' => 'الموارد البشرية',
    'Payroll' => 'الموارد البشرية',
    'Salary' => 'الموارد البشرية',
    'TimeSheet' => 'الموارد البشرية',
    'Overtime' => 'الموارد البشرية',
    'HRSetting' => 'الموارد البشرية',
    'Position' => 'الموارد البشرية',
    'EmployeeDocument' => 'الموارد البشرية',
    'EndOfService' => 'الموارد البشرية',
    
    // إدارة المستندات
    'Document' => 'إدارة المستندات',
    'Signature' => 'إدارة المستندات',
    'Approval' => 'إدارة المستندات',
    'AuditTrail' => 'إدارة المستندات',
    'ActivityLog' => 'إدارة المستندات',
    'Notification' => 'إدارة المستندات',
    
    // التقارير والتحليلات
    'Report' => 'التقارير والتحليلات',
    
    // إعدادات النظام
    'User' => 'إعدادات النظام',
    'Role' => 'إعدادات النظام',
    'Permission' => 'إعدادات النظام',
    'Setting' => 'إعدادات النظام',
    'Company' => 'إعدادات النظام',
    'Branch' => 'إعدادات النظام',
    'Currency' => 'إعدادات النظام',
    'Country' => 'إعدادات النظام',
    'City' => 'إعدادات النظام',
    'Tax' => 'إعدادات النظام',
    'FiscalYear' => 'إعدادات النظام',
    'Workflow' => 'إعدادات النظام',
    'SystemConfig' => 'إعدادات النظام',
    'Sequence' => 'إعدادات النظام',
    'Organization' => 'إعدادات النظام',
];

$updatedCount = 0;

foreach ($files as $file) {
    $filename = basename($file, '.php');
    $content = file_get_contents($file);
    
    // Find the appropriate group
    $group = null;
    foreach ($groupMappings as $prefix => $groupName) {
        if (strpos($filename, $prefix) === 0) {
            $group = $groupName;
            break;
        }
    }
    
    if ($group) {
        // Check if navigationGroup exists
        if (preg_match('/protected static \?string \$navigationGroup\s*=/', $content)) {
            // Update existing
            $newContent = preg_replace(
                "/protected static \?string \\\$navigationGroup\s*=\s*['\"][^'\"]*['\"]\s*;/",
                "protected static ?string \$navigationGroup = '{$group}';",
                $content
            );
        } else {
            // Add after navigationIcon or after class opening
            if (preg_match('/protected static \?string \$navigationIcon/', $content)) {
                $newContent = preg_replace(
                    '/(protected static \?string \$navigationIcon[^;]+;)/',
                    "$1\n    protected static ?string \$navigationGroup = '{$group}';",
                    $content
                );
            } else {
                continue;
            }
        }
        
        if ($newContent !== $content) {
            file_put_contents($file, $newContent);
            echo "Updated: $filename -> $group\n";
            $updatedCount++;
        }
    }
}

echo "\nTotal updated: $updatedCount files\n";
