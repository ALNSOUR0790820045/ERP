<?php

namespace App\Models\Tenders;

use App\Models\Tender;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * موافقة شراء وثائق العطاء
 * Tender Purchase Approval
 */
class TenderPurchaseApproval extends Model
{
    protected $fillable = [
        'tender_id',
        'request_date',
        'requested_by',
        'justification',
        'estimated_cost',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejection_reason',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
        'estimated_cost' => 'decimal:2',
    ];

    // حالات الموافقة
    public const STATUSES = [
        'pending' => 'بانتظار الموافقة',
        'approved' => 'موافق',
        'rejected' => 'مرفوض',
        'deferred' => 'مؤجل',
    ];

    // العلاقات
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Methods
    public function approve(User $user, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);

        // تحديث العطاء
        $this->tender->update(['purchase_approved' => true]);
    }

    public function reject(User $user, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function defer(User $user, ?string $notes = null): void
    {
        $this->update([
            'status' => 'deferred',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }
}
