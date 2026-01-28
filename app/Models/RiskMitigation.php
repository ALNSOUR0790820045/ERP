<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskMitigation extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_risk_id', 'action_number', 'action_type', 'description',
        'responsible_id', 'planned_date', 'actual_date', 'cost_estimate',
        'actual_cost', 'effectiveness', 'status', 'notes',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'actual_date' => 'date',
        'cost_estimate' => 'decimal:3',
        'actual_cost' => 'decimal:3',
        'effectiveness' => 'decimal:2',
    ];

    public function projectRisk(): BelongsTo { return $this->belongsTo(ProjectRisk::class); }
    public function responsible(): BelongsTo { return $this->belongsTo(User::class, 'responsible_id'); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
}
