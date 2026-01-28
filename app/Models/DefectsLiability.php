<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DefectsLiability extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'project_id', 'start_date', 'end_date',
        'duration_months', 'retention_held', 'retention_released',
        'defects_reported', 'defects_rectified', 'defects_outstanding',
        'inspection_dates', 'status', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'retention_held' => 'decimal:3',
        'retention_released' => 'decimal:3',
        'inspection_dates' => 'array',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }

    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeExpired($query) { return $query->where('status', 'expired'); }

    public function getDefectsClearedAttribute(): bool
    {
        return $this->defects_outstanding === 0;
    }
}
