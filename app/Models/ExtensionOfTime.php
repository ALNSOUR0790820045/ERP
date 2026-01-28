<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtensionOfTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'project_id', 'eot_number', 'claim_date',
        'event_description', 'event_start_date', 'event_end_date',
        'delay_type', 'days_claimed', 'days_granted', 'cost_claimed',
        'cost_granted', 'original_completion_date', 'revised_completion_date',
        'supporting_documents', 'contractor_submission', 'engineer_assessment',
        'status', 'assessed_by', 'approved_by', 'approved_at', 'notes',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'event_start_date' => 'date',
        'event_end_date' => 'date',
        'original_completion_date' => 'date',
        'revised_completion_date' => 'date',
        'approved_at' => 'datetime',
        'days_claimed' => 'integer',
        'days_granted' => 'integer',
        'cost_claimed' => 'decimal:3',
        'cost_granted' => 'decimal:3',
        'supporting_documents' => 'array',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function assessor(): BelongsTo { return $this->belongsTo(User::class, 'assessed_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopeRejected($query) { return $query->where('status', 'rejected'); }
}
