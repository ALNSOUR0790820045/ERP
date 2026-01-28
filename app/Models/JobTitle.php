<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobTitle extends Model
{
    protected $fillable = [
        'company_id', 'code', 'name_ar', 'name_en', 'description',
        'min_salary', 'max_salary', 'is_active',
    ];

    protected $casts = [
        'min_salary' => 'decimal:3',
        'max_salary' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
