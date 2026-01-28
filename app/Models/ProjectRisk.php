<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectRisk extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'risk_number', 'risk_category', 'title', 'description',
        'probability', 'impact', 'risk_score', 'risk_level',
        'risk_owner_id', 'identified_date', 'response_strategy',
        'response_plan', 'contingency_plan', 'trigger_conditions',
        'residual_probability', 'residual_impact', 'residual_score',
        'status', 'closed_date', 'closure_reason', 'notes',
    ];

    protected $casts = [
        'identified_date' => 'date',
        'closed_date' => 'date',
        'probability' => 'decimal:2',
        'impact' => 'decimal:2',
        'risk_score' => 'decimal:2',
        'residual_probability' => 'decimal:2',
        'residual_impact' => 'decimal:2',
        'residual_score' => 'decimal:2',
        'trigger_conditions' => 'array',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function riskOwner(): BelongsTo { return $this->belongsTo(User::class, 'risk_owner_id'); }
    public function mitigations(): HasMany { return $this->hasMany(RiskMitigation::class); }

    public function scopeOpen($query) { return $query->where('status', 'open'); }
    public function scopeHigh($query) { return $query->where('risk_level', 'high'); }
    public function scopeCritical($query) { return $query->where('risk_level', 'critical'); }
}
