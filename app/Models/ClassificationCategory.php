<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassificationCategory extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'code',
        'min_value',
        'max_value',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
    ];

    /**
     * المناقصات المرتبطة بهذه الفئة
     */
    public function tenders(): HasMany
    {
        return $this->hasMany(Tender::class);
    }
}
