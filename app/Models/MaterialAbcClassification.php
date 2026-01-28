<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialAbcClassification extends Model
{
    protected $fillable = [
        'material_id',
        'abc_class',
        'annual_consumption_value',
        'percentage_of_total',
        'cumulative_percentage',
        'classification_date',
    ];

    protected $casts = [
        'annual_consumption_value' => 'decimal:3',
        'percentage_of_total' => 'decimal:2',
        'cumulative_percentage' => 'decimal:2',
        'classification_date' => 'date',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function getClassDescriptionAttribute(): string
    {
        return match($this->abc_class) {
            'A' => 'فئة A - قيمة عالية (70-80% من القيمة)',
            'B' => 'فئة B - قيمة متوسطة (15-25% من القيمة)',
            'C' => 'فئة C - قيمة منخفضة (5-10% من القيمة)',
            default => $this->abc_class,
        };
    }
}
