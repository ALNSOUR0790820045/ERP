<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description',
        'is_system',
        'level',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'level' => 'integer',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

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

    public static function getSystemRoles(): array
    {
        return [
            'super_admin' => 'مدير النظام',
            'company_admin' => 'مدير الشركة',
            'project_manager' => 'مدير المشاريع',
            'financial_manager' => 'مدير مالي',
            'site_engineer' => 'مهندس موقع',
            'accountant' => 'محاسب',
            'warehouse_keeper' => 'أمين مستودع',
            'procurement_officer' => 'موظف مشتريات',
            'hr_officer' => 'موظف موارد بشرية',
            'standard_user' => 'مستخدم عادي',
        ];
    }
}
