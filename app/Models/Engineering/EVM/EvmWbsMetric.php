<?php

namespace App\Models\Engineering\EVM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvmWbsMetric extends Model
{
    protected $fillable = [
        'snapshot_id',
        'wbs_id',
        'bac',
        'pv',
        'ev',
        'ac',
        'sv',
        'cv',
        'spi',
        'cpi',
        'percent_complete',
    ];

    protected $casts = [
        'bac' => 'decimal:2',
        'pv' => 'decimal:2',
        'ev' => 'decimal:2',
        'ac' => 'decimal:2',
        'sv' => 'decimal:2',
        'cv' => 'decimal:2',
        'spi' => 'decimal:4',
        'cpi' => 'decimal:4',
        'percent_complete' => 'decimal:2',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(EvmSnapshot::class, 'snapshot_id');
    }

    public function wbs(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ProjectWbs::class, 'wbs_id');
    }

    public function getHealthStatusAttribute(): string
    {
        if ($this->cpi >= 1 && $this->spi >= 1) return 'green';
        if ($this->cpi >= 0.9 && $this->spi >= 0.9) return 'yellow';
        return 'red';
    }
}
