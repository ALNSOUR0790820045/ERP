<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * شاشات/موارد النظام
 * مثل: شاشة الفواتير، شاشة العطاءات، شاشة الموظفين
 */
class SystemScreen extends Model
{
    protected $fillable = [
        'module_id',
        'code',
        'name_ar',
        'name_en',
        'resource_class',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * الوحدة التابعة لها الشاشة
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(SystemModule::class, 'module_id');
    }

    /**
     * الأدوار التي لديها صلاحية على هذه الشاشة
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_screens', 'screen_id', 'role_id')
            ->withPivot(['can_view', 'can_create', 'can_edit', 'can_delete', 'can_export', 'can_print'])
            ->withTimestamps();
    }

    /**
     * الحصول على الاسم حسب اللغة
     */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?? $this->name_ar);
    }

    /**
     * التحقق من صلاحية دور معين على هذه الشاشة
     */
    public function roleHasPermission(int $roleId, string $permission): bool
    {
        $roleScreen = $this->roles()->where('role_id', $roleId)->first();
        
        if (!$roleScreen) {
            return false;
        }

        return match($permission) {
            'view' => $roleScreen->pivot->can_view,
            'create' => $roleScreen->pivot->can_create,
            'edit' => $roleScreen->pivot->can_edit,
            'delete' => $roleScreen->pivot->can_delete,
            'export' => $roleScreen->pivot->can_export,
            'print' => $roleScreen->pivot->can_print,
            default => false,
        };
    }
}
