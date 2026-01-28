<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'legal_name',
        'registration_number',
        'tax_number',
        'vat_number',
        'social_security_number',
        'classification_number',
        'classification_grade',
        'establishment_date',
        'address',
        'city_id',
        'country_id',
        'postal_code',
        'phone',
        'fax',
        'email',
        'website',
        'logo',
        'default_currency_id',
        'fiscal_year_start',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'establishment_date' => 'date',
        'fiscal_year_start' => 'integer',
        'classification_grade' => 'integer',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function fiscalYears(): HasMany
    {
        return $this->hasMany(FiscalYear::class);
    }

    public function currentFiscalYear()
    {
        return $this->fiscalYears()->where('is_current', true)->first();
    }

    public function mainBranch()
    {
        return $this->branches()->where('branch_type', 'main')->first();
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?? $this->name_ar);
    }

    public function getClassificationLabel(): string
    {
        if (!$this->classification_grade) return '-';
        
        return match($this->classification_grade) {
            1 => 'الدرجة الأولى',
            2 => 'الدرجة الثانية',
            3 => 'الدرجة الثالثة',
            4 => 'الدرجة الرابعة',
            5 => 'الدرجة الخامسة',
            default => "الدرجة {$this->classification_grade}",
        };
    }
}
