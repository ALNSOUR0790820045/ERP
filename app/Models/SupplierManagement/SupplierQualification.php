<?php

namespace App\Models\SupplierManagement;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierQualification extends Model
{
    protected $fillable = [
        'supplier_id', 'qualification_type', 'qualification_level', 'status',
        'application_date', 'qualification_date', 'expiry_date', 'reviewed_by',
        'reviewed_at', 'review_notes', 'score', 'criteria_scores',
        'required_documents', 'is_approved', 'metadata',
    ];

    protected $casts = [
        'application_date' => 'date',
        'qualification_date' => 'date',
        'expiry_date' => 'date',
        'reviewed_at' => 'datetime',
        'criteria_scores' => 'array',
        'required_documents' => 'array',
        'metadata' => 'array',
        'is_approved' => 'boolean',
        'score' => 'decimal:2',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }

    public function scopeApproved($q) { return $q->where('status', self::STATUS_APPROVED); }
    public function scopeExpiring($q, $days = 30) {
        return $q->where('expiry_date', '<=', now()->addDays($days))->where('expiry_date', '>', now());
    }

    public function isExpired(): bool { return $this->expiry_date && $this->expiry_date->isPast(); }

    public function approve(User $reviewer, float $score, string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED, 'is_approved' => true,
            'reviewed_by' => $reviewer->id, 'reviewed_at' => now(),
            'score' => $score, 'review_notes' => $notes,
            'qualification_date' => now(), 'expiry_date' => now()->addYear(),
        ]);
        $this->supplier->update(['qualification_status' => 'qualified', 'qualification_date' => now()]);
    }
}
