<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PpeItem extends Model
{
    protected $fillable = [
        'code', 'name_ar', 'name_en', 'category', 'specifications', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function distributions(): HasMany
    {
        return $this->hasMany(PpeDistribution::class);
    }
}
