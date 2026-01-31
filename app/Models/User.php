<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'email',
        'password',
        'username',
        'phone',
        'employee_id',
        'branch_id',
        'role_id', // الدور الرئيسي (للتوافق)
        'language',
        'timezone',
        'is_active',
        'avatar',
        'job_title',
        'must_change_password',
        'two_factor_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    // ==================== العلاقات ====================

    /**
     * الدور الرئيسي (للتوافق مع الكود القديم)
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * جميع أدوار المستخدم (نظام الأدوار المتعددة)
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot(['is_primary', 'assigned_at', 'assigned_by'])
            ->withTimestamps();
    }

    /**
     * الأدوار الوظيفية فقط
     */
    public function jobRoles(): BelongsToMany
    {
        return $this->roles()->where('type', Role::TYPE_JOB);
    }

    /**
     * أدوار العطاءات فقط
     */
    public function tenderRoles(): BelongsToMany
    {
        return $this->roles()->where('type', Role::TYPE_TENDER);
    }

    /**
     * الصلاحيات المؤقتة
     */
    public function temporaryPermissions(): HasMany
    {
        return $this->hasMany(TemporaryPermission::class);
    }

    /**
     * الصلاحيات المؤقتة النشطة
     */
    public function activeTemporaryPermissions(): HasMany
    {
        return $this->temporaryPermissions()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * صلاحيات المراحل للمستخدم
     */
    public function userStagePermissions(): HasMany
    {
        return $this->hasMany(UserStagePermission::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // ==================== التحقق من الأدوار ====================

    /**
     * التحقق من دور معين (يدعم الأدوار المتعددة)
     */
    public function hasRole(string $roleCode): bool
    {
        // تحقق من الدور الرئيسي أولاً
        if ($this->role?->code === $roleCode) {
            return true;
        }
        
        // تحقق من الأدوار المتعددة
        return $this->roles()->where('code', $roleCode)->exists();
    }

    /**
     * التحقق من أي دور من مجموعة
     */
    public function hasAnyRole(array $roleCodes): bool
    {
        if (in_array($this->role?->code, $roleCodes)) {
            return true;
        }
        
        return $this->roles()->whereIn('code', $roleCodes)->exists();
    }

    /**
     * التحقق من نوع دور معين
     */
    public function hasRoleType(string $type): bool
    {
        if ($this->role?->type === $type) {
            return true;
        }
        
        return $this->roles()->where('type', $type)->exists();
    }

    // ==================== التحقق من الصلاحيات ====================

    /**
     * التحقق من صلاحية معينة
     */
    public function hasPermission(string $permissionCode): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // تحقق من الدور الرئيسي
        if ($this->role?->hasPermission($permissionCode)) {
            return true;
        }

        // تحقق من الأدوار المتعددة
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permissionCode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * التحقق من صلاحية مؤقتة على سجل معين
     */
    public function hasTemporaryPermission(Model $record, string $permission): bool
    {
        return TemporaryPermission::check($this->id, $record, $permission);
    }

    /**
     * التحقق من أي صلاحية من مجموعة
     */
    public function hasAnyPermission(array $permissionCodes): bool
    {
        foreach ($permissionCodes as $code) {
            if ($this->hasPermission($code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * التحقق من جميع الصلاحيات
     */
    public function hasAllPermissions(array $permissionCodes): bool
    {
        foreach ($permissionCodes as $code) {
            if (!$this->hasPermission($code)) {
                return false;
            }
        }
        return true;
    }

    // ==================== إدارة الأدوار ====================

    /**
     * إضافة دور للمستخدم
     */
    public function assignRole(Role|int $role, bool $isPrimary = false): void
    {
        $roleId = $role instanceof Role ? $role->id : $role;
        
        $this->roles()->syncWithoutDetaching([
            $roleId => [
                'is_primary' => $isPrimary,
                'assigned_at' => now(),
                'assigned_by' => auth()->id(),
            ]
        ]);
        
        // إذا كان الدور الأساسي، حدث role_id
        if ($isPrimary) {
            $this->update(['role_id' => $roleId]);
        }
    }

    /**
     * إزالة دور من المستخدم
     */
    public function removeRole(Role|int $role): void
    {
        $roleId = $role instanceof Role ? $role->id : $role;
        $this->roles()->detach($roleId);
    }

    /**
     * مزامنة الأدوار
     */
    public function syncRoles(array $roleIds): void
    {
        $syncData = [];
        foreach ($roleIds as $roleId) {
            $syncData[$roleId] = [
                'assigned_at' => now(),
                'assigned_by' => auth()->id(),
            ];
        }
        $this->roles()->sync($syncData);
    }

    // ==================== المساعدين ====================

    public function getDisplayNameAttribute(): string
    {
        if (app()->getLocale() === 'ar' && $this->name_ar) {
            return $this->name_ar;
        }
        return $this->name_en ?? $this->name;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->code === 'super_admin' || 
               $this->roles()->where('code', 'super_admin')->exists();
    }

    public function isCompanyAdmin(): bool
    {
        return $this->role?->code === 'company_admin' ||
               $this->roles()->where('code', 'company_admin')->exists();
    }

    /**
     * الحصول على جميع الوحدات المتاحة للمستخدم
     */
    public function getAvailableModules()
    {
        if ($this->isSuperAdmin()) {
            return SystemModule::where('is_active', true)->get();
        }

        $moduleIds = collect();
        
        // من الدور الرئيسي
        if ($this->role) {
            $moduleIds = $moduleIds->merge($this->role->systemModules()->pluck('system_modules.id'));
        }
        
        // من الأدوار المتعددة
        foreach ($this->roles as $role) {
            $moduleIds = $moduleIds->merge($role->systemModules()->pluck('system_modules.id'));
        }

        return SystemModule::whereIn('id', $moduleIds->unique())->get();
    }

    /**
     * الحصول على جميع الشاشات المتاحة للمستخدم
     */
    public function getAvailableScreens()
    {
        if ($this->isSuperAdmin()) {
            return SystemScreen::where('is_active', true)->get();
        }

        $screenIds = collect();
        
        if ($this->role) {
            $screenIds = $screenIds->merge($this->role->systemScreens()->pluck('system_screens.id'));
        }
        
        foreach ($this->roles as $role) {
            $screenIds = $screenIds->merge($role->systemScreens()->pluck('system_screens.id'));
        }

        return SystemScreen::whereIn('id', $screenIds->unique())->get();
    }
}
