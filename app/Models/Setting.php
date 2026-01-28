<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = [
        'company_id',
        'group',
        'key',
        'value',
        'type',
        'description',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getValueAttribute($value)
    {
        return match($this->type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'float' => (float) $value,
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    public function setValueAttribute($value)
    {
        $this->attributes['value'] = match($this->type) {
            'json', 'array' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    public static function get(string $key, $default = null, ?int $companyId = null)
    {
        $parts = explode('.', $key);
        $group = $parts[0] ?? 'general';
        $settingKey = $parts[1] ?? $key;
        
        $setting = self::where('group', $group)
            ->where('key', $settingKey)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->first();
            
        return $setting?->value ?? $default;
    }

    public static function set(string $key, $value, ?int $companyId = null, string $type = 'string'): self
    {
        $parts = explode('.', $key);
        $group = $parts[0] ?? 'general';
        $settingKey = $parts[1] ?? $key;
        
        return self::updateOrCreate(
            [
                'company_id' => $companyId,
                'group' => $group,
                'key' => $settingKey,
            ],
            [
                'value' => $value,
                'type' => $type,
            ]
        );
    }

    public static function getGroup(string $group, ?int $companyId = null): array
    {
        return self::where('group', $group)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->pluck('value', 'key')
            ->toArray();
    }

    public static function getDefaultGroups(): array
    {
        return [
            'general' => 'عام',
            'company' => 'الشركة',
            'finance' => 'المالية',
            'notifications' => 'الإشعارات',
            'security' => 'الأمان',
            'appearance' => 'المظهر',
        ];
    }
}
