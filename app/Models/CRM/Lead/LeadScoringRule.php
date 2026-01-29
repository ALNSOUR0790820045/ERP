<?php

namespace App\Models\CRM\Lead;

use Illuminate\Database\Eloquent\Model;

class LeadScoringRule extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'category',
        'field_name',
        'operator',
        'field_value',
        'points',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'points' => 'integer',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    // Methods
    public static function getOperators(): array
    {
        return [
            'equals' => 'يساوي',
            'not_equals' => 'لا يساوي',
            'contains' => 'يحتوي على',
            'greater_than' => 'أكبر من',
            'less_than' => 'أصغر من',
            'is_empty' => 'فارغ',
            'is_not_empty' => 'غير فارغ',
        ];
    }

    public static function getLeadFields(): array
    {
        return [
            'company_name' => 'اسم الشركة',
            'industry' => 'الصناعة',
            'company_size' => 'حجم الشركة',
            'country' => 'الدولة',
            'city' => 'المدينة',
            'estimated_value' => 'القيمة المتوقعة',
            'email' => 'البريد الإلكتروني',
            'phone' => 'الهاتف',
            'website' => 'الموقع الإلكتروني',
        ];
    }
}
