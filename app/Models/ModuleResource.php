<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleResource extends Model
{
    protected $fillable = [
        'module_id',
        'code',
        'name_ar',
        'name_en',
        'filament_resource',
        'is_main',
        'sort_order',
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * الوحدة التابع لها المورد
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * الحصول على اسم المورد حسب اللغة
     */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?? $this->name_ar);
    }

    /**
     * الحصول على كلاس Filament Resource
     */
    public function getFilamentResourceClass(): ?string
    {
        if (!$this->filament_resource) {
            return null;
        }

        $className = "App\\Filament\\Resources\\{$this->filament_resource}";
        
        return class_exists($className) ? $className : null;
    }

    /**
     * الحصول على الموارد الرئيسية
     */
    public static function getMainResources()
    {
        return self::where('is_main', true)
            ->orderBy('sort_order')
            ->get();
    }
}
