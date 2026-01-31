<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionType extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description',
        'icon',
        'color',
        'category',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    // ثوابت أنواع الصلاحيات
    public const VIEW = 'view';
    public const CREATE = 'create';
    public const UPDATE = 'update';
    public const DELETE = 'delete';
    public const APPROVE = 'approve';
    public const REJECT = 'reject';
    public const ESCALATE = 'escalate';
    public const DELEGATE = 'delegate';
    public const PRINT = 'print';
    public const EXPORT = 'export';

    // فئات الصلاحيات
    public const CATEGORY_BASIC = 'basic';
    public const CATEGORY_WORKFLOW = 'workflow';
    public const CATEGORY_EXPORT = 'export';

    /**
     * صلاحيات المستخدمين من هذا النوع
     */
    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserStagePermission::class);
    }

    /**
     * صلاحيات الأدوار من هذا النوع
     */
    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RoleStagePermission::class);
    }

    /**
     * الحصول على اسم نوع الصلاحية حسب اللغة
     */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?? $this->name_ar);
    }

    /**
     * الحصول على نوع صلاحية بالكود
     */
    public static function getByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }

    /**
     * الحصول على الصلاحيات الأساسية
     */
    public static function getBasicPermissions()
    {
        return self::where('category', self::CATEGORY_BASIC)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * الحصول على صلاحيات سير العمل
     */
    public static function getWorkflowPermissions()
    {
        return self::where('category', self::CATEGORY_WORKFLOW)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * الحصول على جميع الصلاحيات مجمعة بالفئة
     */
    public static function getGroupedByCategory()
    {
        return self::orderBy('sort_order')
            ->get()
            ->groupBy('category');
    }

    /**
     * الحصول على اسم الفئة
     */
    public function getCategoryNameAttribute(): string
    {
        return match ($this->category) {
            self::CATEGORY_BASIC => 'أساسية',
            self::CATEGORY_WORKFLOW => 'سير العمل',
            self::CATEGORY_EXPORT => 'تصدير',
            default => $this->category,
        };
    }
}
