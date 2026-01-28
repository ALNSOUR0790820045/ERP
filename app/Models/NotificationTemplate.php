<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'module',
        'event_type',
        'subject_ar',
        'subject_en',
        'body_ar',
        'body_en',
        'sms_body_ar',
        'sms_body_en',
        'channels',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'channels' => 'array',
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    // الثوابت
    public const CHANNELS = [
        'database' => 'قاعدة البيانات',
        'email' => 'بريد إلكتروني',
        'sms' => 'رسالة نصية',
    ];

    public const MODULES = [
        'finance' => 'المالية',
        'hr' => 'الموارد البشرية',
        'projects' => 'المشاريع',
        'contracts' => 'العقود',
        'warehouse' => 'المستودعات',
        'tenders' => 'المناقصات',
    ];

    public const EVENT_TYPES = [
        'created' => 'إنشاء',
        'updated' => 'تحديث',
        'approved' => 'اعتماد',
        'rejected' => 'رفض',
        'reminder' => 'تذكير',
        'alert' => 'تنبيه',
    ];

    /**
     * استبدال المتغيرات في القالب
     */
    public function render(array $data, string $field, string $locale = 'ar'): string
    {
        $fieldName = $field . '_' . $locale;
        $content = $this->$fieldName ?? $this->{$field . '_ar'};

        foreach ($data as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
        }

        return $content;
    }

    /**
     * الحصول على القالب بالكود
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('code', $code)->where('is_active', true)->first();
    }
}
