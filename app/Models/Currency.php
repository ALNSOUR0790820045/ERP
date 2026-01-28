<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'symbol',
        'exchange_rate',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function tenders(): HasMany
    {
        return $this->hasMany(Tender::class);
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'en' && $this->name_en 
            ? $this->name_en 
            : $this->name_ar;
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }
}
