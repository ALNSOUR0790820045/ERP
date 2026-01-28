<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Specialization extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'code',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
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
}
