<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleStage extends Model
{
    protected $fillable = [
        'module_id',
        'code',
        'name_ar',
        'name_en',
        'description',
        'color',
        'icon',
        'sort_order',
        'is_initial',
        'is_final',
    ];

    protected $casts = [
        'is_initial' => 'boolean',
        'is_final' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * الوحدة التابعة لها المرحلة
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * صلاحيات المستخدمين على هذه المرحلة
     */
    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserStagePermission::class, 'stage_id');
    }

    /**
     * صلاحيات الأدوار على هذه المرحلة
     */
    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RoleStagePermission::class, 'stage_id');
    }

    /**
     * الحصول على اسم المرحلة حسب اللغة
     */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?? $this->name_ar);
    }

    /**
     * الحصول على المرحلة الابتدائية للوحدة
     */
    public static function getInitialStage(int $moduleId): ?self
    {
        return self::where('module_id', $moduleId)
            ->where('is_initial', true)
            ->first();
    }

    /**
     * الحصول على المراحل النهائية للوحدة
     */
    public static function getFinalStages(int $moduleId)
    {
        return self::where('module_id', $moduleId)
            ->where('is_final', true)
            ->get();
    }

    /**
     * الحصول على المرحلة التالية
     */
    public function getNextStage(): ?self
    {
        return self::where('module_id', $this->module_id)
            ->where('sort_order', '>', $this->sort_order)
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * الحصول على المرحلة السابقة
     */
    public function getPreviousStage(): ?self
    {
        return self::where('module_id', $this->module_id)
            ->where('sort_order', '<', $this->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();
    }

    /**
     * قائمة الألوان المتاحة
     */
    public static function getAvailableColors(): array
    {
        return [
            'gray' => 'رمادي',
            'blue' => 'أزرق',
            'green' => 'أخضر',
            'yellow' => 'أصفر',
            'orange' => 'برتقالي',
            'red' => 'أحمر',
            'purple' => 'بنفسجي',
            'pink' => 'وردي',
        ];
    }
}
