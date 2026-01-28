<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomFieldDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'field_name',
        'field_label_ar',
        'field_label_en',
        'field_type',
        'options',
        'default_value',
        'validation_rules',
        'is_required',
        'is_searchable',
        'is_filterable',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'validation_rules' => 'array',
        'is_required' => 'boolean',
        'is_searchable' => 'boolean',
        'is_filterable' => 'boolean',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    // الثوابت
    public const FIELD_TYPES = [
        'text' => 'نص قصير',
        'textarea' => 'نص طويل',
        'number' => 'رقم',
        'decimal' => 'رقم عشري',
        'date' => 'تاريخ',
        'datetime' => 'تاريخ ووقت',
        'select' => 'قائمة منسدلة',
        'multiselect' => 'قائمة متعددة',
        'checkbox' => 'مربع اختيار',
        'radio' => 'أزرار راديو',
        'file' => 'ملف',
        'image' => 'صورة',
        'url' => 'رابط',
        'email' => 'بريد إلكتروني',
        'phone' => 'رقم هاتف',
    ];

    public const ENTITY_TYPES = [
        'App\\Models\\Contract' => 'العقود',
        'App\\Models\\Project' => 'المشاريع',
        'App\\Models\\Employee' => 'الموظفين',
        'App\\Models\\Material' => 'المواد',
        'App\\Models\\Customer' => 'العملاء',
        'App\\Models\\Supplier' => 'الموردين',
    ];

    /**
     * الحصول على الحقول المخصصة لكيان معين
     */
    public static function getForEntity(string $entityType): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('entity_type', $entityType)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }
}
