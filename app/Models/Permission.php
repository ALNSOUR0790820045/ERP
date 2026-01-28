<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'code',
        'module',
        'resource',
        'action',
        'name_ar',
        'name_en',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withTimestamps();
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?? $this->name_ar);
    }

    public function getActionLabel(): string
    {
        return match($this->action) {
            'view' => 'عرض',
            'create' => 'إضافة',
            'update' => 'تعديل',
            'delete' => 'حذف',
            'export' => 'تصدير',
            'import' => 'استيراد',
            'approve' => 'اعتماد',
            'print' => 'طباعة',
            default => $this->action,
        };
    }

    public static function getModules(): array
    {
        return [
            'core' => 'النظام الأساسي',
            'tenders' => 'العطاءات',
            'contracts' => 'العقود',
            'projects' => 'المشاريع',
            'billing' => 'الفوترة',
            'suppliers' => 'الموردين',
            'procurement' => 'المشتريات',
            'warehouse' => 'المستودعات',
            'manufacturing' => 'التصنيع',
            'equipment' => 'المعدات',
            'finance' => 'المالية',
            'hr' => 'الموارد البشرية',
            'crm' => 'العملاء',
            'quality' => 'الجودة',
            'hse' => 'الصحة والسلامة',
            'documents' => 'الوثائق',
            'reports' => 'التقارير',
        ];
    }

    public static function getActions(): array
    {
        return ['view', 'create', 'update', 'delete', 'export', 'import', 'approve', 'print'];
    }

    public static function generateForResource(string $module, string $resource, string $resourceNameAr): array
    {
        $permissions = [];
        $actions = [
            'view' => 'عرض',
            'create' => 'إضافة',
            'update' => 'تعديل',
            'delete' => 'حذف',
            'export' => 'تصدير',
        ];

        foreach ($actions as $action => $actionNameAr) {
            $permissions[] = [
                'code' => "{$module}.{$resource}.{$action}",
                'module' => $module,
                'resource' => $resource,
                'action' => $action,
                'name_ar' => "{$actionNameAr} {$resourceNameAr}",
                'name_en' => ucfirst($action) . ' ' . ucfirst($resource),
            ];
        }

        return $permissions;
    }
}
