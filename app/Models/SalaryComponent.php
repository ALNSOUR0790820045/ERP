<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryComponent extends Model
{
    protected $fillable = [
        'company_id', 'code', 'name_ar', 'name_en', 'component_type',
        'calculation_type', 'default_value', 'is_taxable', 'is_active',
    ];

    protected $casts = [
        'default_value' => 'decimal:3',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employeeComponents(): HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class, 'component_id');
    }
}
