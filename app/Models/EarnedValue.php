<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EarnedValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'period_date', 'period_type', 'bac', 'pv', 'ev', 'ac',
        'sv', 'cv', 'spi', 'cpi', 'tcpi', 'eac', 'etc', 'vac',
        'percent_complete_planned', 'percent_complete_earned',
        'cumulative_pv', 'cumulative_ev', 'cumulative_ac',
        'status', 'notes',
    ];

    protected $casts = [
        'period_date' => 'date',
        'bac' => 'decimal:3',
        'pv' => 'decimal:3',
        'ev' => 'decimal:3',
        'ac' => 'decimal:3',
        'sv' => 'decimal:3',
        'cv' => 'decimal:3',
        'spi' => 'decimal:4',
        'cpi' => 'decimal:4',
        'tcpi' => 'decimal:4',
        'eac' => 'decimal:3',
        'etc' => 'decimal:3',
        'vac' => 'decimal:3',
        'percent_complete_planned' => 'decimal:2',
        'percent_complete_earned' => 'decimal:2',
        'cumulative_pv' => 'decimal:3',
        'cumulative_ev' => 'decimal:3',
        'cumulative_ac' => 'decimal:3',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }

    public function getScheduleStatusAttribute(): string
    {
        if ($this->spi >= 1) return 'ahead';
        if ($this->spi >= 0.9) return 'on_track';
        return 'behind';
    }

    public function getCostStatusAttribute(): string
    {
        if ($this->cpi >= 1) return 'under_budget';
        if ($this->cpi >= 0.9) return 'on_budget';
        return 'over_budget';
    }
}
