<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * إعدادات الفوترة الإلكترونية JoFotara
 */
class JofotaraSetting extends Model
{
    protected $fillable = [
        'taxpayer_id',
        'activity_number',
        'api_key',
        'api_secret',
        'certificate_path',
        'private_key_path',
        'environment',
        'api_base_url',
        'is_active',
        'last_sync',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_sync' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    public function getApiBaseUrlAttribute($value): string
    {
        if ($value) return $value;
        
        return $this->environment === 'production'
            ? 'https://api.jofotara.gov.jo/v1'
            : 'https://sandbox.jofotara.gov.jo/v1';
    }

    public static function getActive(): ?self
    {
        return static::where('is_active', true)->first();
    }
}
