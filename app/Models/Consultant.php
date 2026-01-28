<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consultant extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'registration_number',
        'classification',
        'contact_person',
        'phone',
        'mobile',
        'email',
        'website',
        'address',
        'specializations',
        'notes',
        'is_active',
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
