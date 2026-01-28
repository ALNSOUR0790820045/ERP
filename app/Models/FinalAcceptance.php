<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalAcceptance extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'project_id', 'acceptance_number', 'acceptance_date',
        'defects_liability_id', 'outstanding_works', 'outstanding_value',
        'retention_to_release', 'final_certificate_value',
        'inspection_date', 'inspection_report', 'acceptance_committee',
        'status', 'issued_by', 'approved_by', 'approved_at', 'notes',
    ];

    protected $casts = [
        'acceptance_date' => 'date',
        'inspection_date' => 'date',
        'approved_at' => 'datetime',
        'outstanding_value' => 'decimal:3',
        'retention_to_release' => 'decimal:3',
        'final_certificate_value' => 'decimal:3',
        'outstanding_works' => 'array',
        'acceptance_committee' => 'array',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function defectsLiability(): BelongsTo { return $this->belongsTo(DefectsLiability::class); }
    public function issuer(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeApproved($query) { return $query->where('status', 'approved'); }
}
