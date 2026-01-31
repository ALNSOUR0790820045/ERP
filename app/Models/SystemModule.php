<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * وحدات النظام الرئيسية
 * مثل: المالية، المشاريع، العطاءات، الموارد البشرية
 */
class SystemModule extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description',
        'icon',
        'color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * الشاشات التابعة لهذه الوحدة
     */
    public function screens(): HasMany
    {
        return $this->hasMany(SystemScreen::class, 'module_id');
    }

    /**
     * الأدوار التي لديها صلاحية على هذه الوحدة
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_modules', 'module_id', 'role_id')
            ->withPivot('full_access')
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
     * الحصول على الوحدات النشطة
     */
    public static function getActiveModules()
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * الحصول على أيقونة الوحدة
     */
    public function getIconClass(): string
    {
        return $this->icon ?? 'heroicon-o-squares-2x2';
    }
}
