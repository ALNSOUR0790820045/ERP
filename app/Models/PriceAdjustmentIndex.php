<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceAdjustmentIndex extends Model
{
    protected $fillable = [
        'contract_id',
        'index_name',
        'index_code',
        'weight',
        'base_value',
        'base_date',
        'source',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'base_value' => 'decimal:4',
        'base_date' => 'date',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(PriceAdjustmentReading::class)->orderByDesc('reading_date');
    }

    public function getLatestReadingAttribute()
    {
        return $this->readings()->latest('reading_date')->first();
    }

    public function getCurrentRatioAttribute(): ?float
    {
        $latest = $this->latest_reading;
        if (!$latest || $this->base_value == 0) {
            return null;
        }
        return $latest->value / $this->base_value;
    }
}
