<?php

namespace App\Models;

use App\Enums\OwnerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Owner extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'type',
        'registration_number',
        'tax_number',
        'contact_person',
        'phone',
        'mobile',
        'email',
        'website',
        'address',
        'city',
        'country',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'type' => OwnerType::class,
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

    public function getFullContactAttribute(): string
    {
        $parts = array_filter([
            $this->contact_person,
            $this->phone,
            $this->email,
        ]);
        return implode(' | ', $parts);
    }
}
