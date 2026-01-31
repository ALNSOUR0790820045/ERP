<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
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
     * الموارد التابعة للوحدة
     */
    public function resources(): HasMany
    {
        return $this->hasMany(ModuleResource::class)->orderBy('sort_order');
    }

    /**
     * مراحل الوحدة
     */
    public function stages(): HasMany
    {
        return $this->hasMany(ModuleStage::class)->orderBy('sort_order');
    }

    /**
     * صلاحيات المستخدمين على هذه الوحدة
     */
    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserStagePermission::class);
    }

    /**
     * صلاحيات الأدوار على هذه الوحدة
     */
    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RoleStagePermission::class);
    }

    /**
     * الحصول على اسم الوحدة حسب اللغة
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
     * قائمة الأيقونات المتاحة
     */
    public static function getAvailableIcons(): array
    {
        return [
            'heroicon-o-document-text' => 'وثيقة',
            'heroicon-o-building-office' => 'مبنى',
            'heroicon-o-banknotes' => 'مالية',
            'heroicon-o-users' => 'مستخدمين',
            'heroicon-o-cube' => 'مخزون',
            'heroicon-o-shopping-cart' => 'مشتريات',
            'heroicon-o-chart-bar' => 'تقارير',
            'heroicon-o-cog-6-tooth' => 'إعدادات',
        ];
    }

    /**
     * قائمة الألوان المتاحة
     */
    public static function getAvailableColors(): array
    {
        return [
            'gray' => 'رمادي',
            'primary' => 'أساسي',
            'success' => 'أخضر',
            'warning' => 'برتقالي',
            'danger' => 'أحمر',
            'info' => 'أزرق',
            'purple' => 'بنفسجي',
        ];
    }
}
