<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'symbol',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function boqItems(): HasMany
    {
        return $this->hasMany(BoqItem::class);
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'en' && $this->name_en 
            ? $this->name_en 
            : $this->name_ar;
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->symbol})";
    }
}
