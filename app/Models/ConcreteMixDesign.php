<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConcreteMixDesign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'mix_code',
        'mix_name',
        'grade',
        'target_slump',
        'water_cement_ratio',
        'components',
        'standard_cost',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'target_slump' => 'decimal:2',
        'water_cement_ratio' => 'decimal:3',
        'components' => 'array',
        'standard_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(ConcreteBatch::class, 'mix_design_id');
    }

    public function getTotalVolumeProducedAttribute(): float
    {
        return $this->batches()->sum('volume');
    }
}
