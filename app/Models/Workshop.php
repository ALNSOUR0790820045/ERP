<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workshop extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'name_en',
        'type',
        'location',
        'capacity',
        'capacity_unit',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'capacity' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function dailyLogs(): HasMany
    {
        return $this->hasMany(DailyProductionLog::class);
    }

    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            'concrete' => 'محطة خرسانة',
            'steel' => 'ورشة حديد',
            'carpentry' => 'نجارة',
            'metalwork' => 'حدادة',
            'other' => 'أخرى',
            default => $this->type,
        };
    }
}
