<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'project_id', 'milestone_number', 'name', 'description',
        'planned_date', 'actual_date', 'payment_percentage', 'payment_amount',
        'deliverables', 'acceptance_criteria', 'status',
        'completed_at', 'verified_by', 'notes',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'actual_date' => 'date',
        'completed_at' => 'datetime',
        'payment_percentage' => 'decimal:2',
        'payment_amount' => 'decimal:3',
        'deliverables' => 'array',
        'acceptance_criteria' => 'array',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
    public function scopeOverdue($query) { return $query->where('status', 'pending')->where('planned_date', '<', now()); }
}
