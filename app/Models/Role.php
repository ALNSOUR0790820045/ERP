<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'code',
        'name_ar',
        'name_en',
        'type', // system, job, tender
        'description',
        'icon',
        'color',
        'is_system',
        'is_active',
        'level',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'level' => 'integer',
    ];

    // أنواع الأدوار
    const TYPE_SYSTEM = 'system';  // أدوار النظام (مدير النظام)
    const TYPE_JOB = 'job';        // أدوار وظيفية (مدير مالي، محاسب)
    const TYPE_TENDER = 'tender';  // أدوار العطاءات (مسعّر، محلل)

    /**
     * المستخدمين المرتبطين بهذا الدور
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot(['is_primary', 'assigned_at', 'assigned_by'])
            ->withTimestamps();
    }

    /**
     * الوحدات المرتبطة بالدور
     */
    public function systemModules(): BelongsToMany
    {
        return $this->belongsToMany(SystemModule::class, 'role_modules', 'role_id', 'module_id')
            ->withPivot('full_access')
            ->withTimestamps();
    }

    /**
     * الشاشات المرتبطة بالدور مع الصلاحيات
     */
    public function systemScreens(): BelongsToMany
    {
        return $this->belongsToMany(SystemScreen::class, 'role_screens', 'role_id', 'screen_id')
            ->withPivot(['can_view', 'can_create', 'can_edit', 'can_delete', 'can_export', 'can_print'])
            ->withTimestamps();
    }

    /**
     * Get available modules for roles (للتوافق مع الكود القديم)
     */
    public static function getModules(): array
    {
        $modules = SystemModule::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name_ar', 'code')
            ->toArray();
            
        return !empty($modules) ? $modules : [
            'core' => 'النظام الأساسي',
            'tenders' => 'العطاءات',
            'contracts' => 'العقود',
            'projects' => 'المشاريع',
            'finance' => 'المالية',
            'hr' => 'الموارد البشرية',
            'inventory' => 'المخزون',
            'procurement' => 'المشتريات',
        ];
    }

    /**
     * التحقق من صلاحية الدور على وحدة معينة
     */
    public function hasModuleAccess(string $moduleCode): bool
    {
        return $this->systemModules()
            ->where('code', $moduleCode)
            ->exists();
    }

    /**
     * التحقق من صلاحية كاملة على وحدة
     */
    public function hasFullModuleAccess(string $moduleCode): bool
    {
        $module = $this->systemModules()
            ->where('code', $moduleCode)
            ->first();
            
        return $module && $module->pivot->full_access;
    }

    /**
     * التحقق من صلاحية على شاشة معينة
     */
    public function hasScreenAccess(string $screenCode, string $permission = 'view'): bool
    {
        $screen = $this->systemScreens()
            ->where('code', $screenCode)
            ->first();
            
        if (!$screen) {
            return false;
        }

        return match($permission) {
            'view' => $screen->pivot->can_view,
            'create' => $screen->pivot->can_create,
            'edit' => $screen->pivot->can_edit,
            'delete' => $screen->pivot->can_delete,
            'export' => $screen->pivot->can_export,
            'print' => $screen->pivot->can_print,
            default => false,
        };
    }

    /**
     * الحصول على الوحدات المتاحة للدور
     */
    public function getAvailableModules()
    {
        return $this->systemModules()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * الحصول على الشاشات المتاحة للدور في وحدة معينة
     */
    public function getAvailableScreens(?int $moduleId = null)
    {
        $query = $this->systemScreens()
            ->where('is_active', true);
            
        if ($moduleId) {
            $query->where('module_id', $moduleId);
        }
        
        return $query->orderBy('sort_order')->get();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps();
    }

    /**
     * صلاحيات المراحل للدور
     */
    public function stagePermissions(): HasMany
    {
        return $this->hasMany(RoleStagePermission::class);
    }

    // users() تم نقلها للأعلى لتستخدم BelongsToMany مع user_roles

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?? $this->name_ar);
    }

    public function hasPermission(string $permissionCode): bool
    {
        return $this->permissions()->where('code', $permissionCode)->exists();
    }

    public function givePermission(string|Permission $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('code', $permission)->firstOrFail();
        }
        
        $this->permissions()->syncWithoutDetaching([$permission->id]);
    }

    public function revokePermission(string|Permission $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('code', $permission)->first();
            if (!$permission) return;
        }
        
        $this->permissions()->detach($permission->id);
    }

    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    public function getPermissionsByModule(): array
    {
        return $this->permissions()
            ->get()
            ->groupBy('module')
            ->toArray();
    }

    // Scopes
    public function scopeSystem($query)
    {
        return $query->where('type', self::TYPE_SYSTEM);
    }

    public function scopeJob($query)
    {
        return $query->where('type', self::TYPE_JOB);
    }

    public function scopeTender($query)
    {
        return $query->where('type', self::TYPE_TENDER);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * الأدوار الوظيفية المتاحة
     */
    public static function getJobRoles(): array
    {
        return [
            'financial_manager' => 'مدير مالي',
            'accountant' => 'محاسب',
            'project_manager' => 'مدير مشاريع',
            'site_engineer' => 'مهندس موقع',
            'hr_manager' => 'مدير موارد بشرية',
            'warehouse_manager' => 'مدير مخازن',
            'procurement_manager' => 'مدير مشتريات',
            'secretary' => 'سكرتير/ة',
        ];
    }

    /**
     * أدوار العطاءات
     */
    public static function getTenderRoles(): array
    {
        return [
            'tender_manager' => 'مدير عطاءات',
            'pricing_approver' => 'معتمد تسعير العطاءات',
            'tender_decision_maker' => 'صاحب قرار العطاءات',
            'tender_pricer' => 'مسعّر عطاءات',
            'tender_analyst' => 'محلل عطاءات',
            'tender_submitter' => 'مقدم عطاءات',
            'tender_monitor' => 'راصد عطاءات',
        ];
    }

    public static function getSystemRoles(): array
    {
        return [
            'super_admin' => 'مدير النظام',
        ];
    }

    /**
     * جميع الأدوار حسب النوع
     */
    public static function getAllRolesByType(): array
    {
        return [
            'system' => self::getSystemRoles(),
            'job' => self::getJobRoles(),
            'tender' => self::getTenderRoles(),
        ];
    }
}
