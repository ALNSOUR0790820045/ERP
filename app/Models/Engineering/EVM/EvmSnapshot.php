<?php

namespace App\Models\Engineering\EVM;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvmSnapshot extends Model
{
    protected $fillable = [
        'project_id',
        'snapshot_date',
        'data_date',
        'bac',
        'pv',
        'ev',
        'ac',
        'sv',
        'cv',
        'spi',
        'cpi',
        'eac',
        'etc',
        'vac',
        'tcpi',
        'percent_complete',
        'percent_spent',
        'analysis_notes',
        'created_by',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'data_date' => 'date',
        'bac' => 'decimal:2',
        'pv' => 'decimal:2',
        'ev' => 'decimal:2',
        'ac' => 'decimal:2',
        'sv' => 'decimal:2',
        'cv' => 'decimal:2',
        'spi' => 'decimal:4',
        'cpi' => 'decimal:4',
        'eac' => 'decimal:2',
        'etc' => 'decimal:2',
        'vac' => 'decimal:2',
        'tcpi' => 'decimal:4',
        'percent_complete' => 'decimal:2',
        'percent_spent' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function wbsMetrics(): HasMany
    {
        return $this->hasMany(EvmWbsMetric::class, 'snapshot_id');
    }

    public function report(): HasOne
    {
        return $this->hasOne(EvmReport::class, 'snapshot_id');
    }

    /**
     * Calculate EVM metrics from raw values
     */
    public static function calculateMetrics(float $bac, float $pv, float $ev, float $ac): array
    {
        $sv = $ev - $pv; // Schedule Variance
        $cv = $ev - $ac; // Cost Variance
        $spi = $pv > 0 ? $ev / $pv : 0; // Schedule Performance Index
        $cpi = $ac > 0 ? $ev / $ac : 0; // Cost Performance Index
        
        // Estimate at Completion (using CPI method)
        $eac = $cpi > 0 ? $bac / $cpi : $bac;
        
        // Estimate to Complete
        $etc = $eac - $ac;
        
        // Variance at Completion
        $vac = $bac - $eac;
        
        // To Complete Performance Index
        $tcpi = ($bac - $ev) > 0 && ($bac - $ac) > 0 
            ? ($bac - $ev) / ($bac - $ac) 
            : 1;

        return [
            'sv' => round($sv, 2),
            'cv' => round($cv, 2),
            'spi' => round($spi, 4),
            'cpi' => round($cpi, 4),
            'eac' => round($eac, 2),
            'etc' => round($etc, 2),
            'vac' => round($vac, 2),
            'tcpi' => round($tcpi, 4),
            'percent_complete' => $bac > 0 ? round(($ev / $bac) * 100, 2) : 0,
            'percent_spent' => $bac > 0 ? round(($ac / $bac) * 100, 2) : 0,
        ];
    }

    public function getHealthStatusAttribute(): string
    {
        if ($this->cpi >= 1 && $this->spi >= 1) return 'on_track';
        if ($this->cpi >= 0.9 && $this->spi >= 0.9) return 'at_risk';
        return 'critical';
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('snapshot_date', 'desc');
    }
}
