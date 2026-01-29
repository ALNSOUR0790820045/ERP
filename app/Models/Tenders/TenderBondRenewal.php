<?php

namespace App\Models\Tenders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تجديد الكفالات
 * Tender Bond Renewals
 */
class TenderBondRenewal extends Model
{
    protected $fillable = [
        'bond_id',
        'renewal_number',
        'request_date',
        'current_expiry_date',
        'new_expiry_date',
        'extension_days',
        'reason',
        'renewal_fee',
        'commission_amount',
        'new_bond_number',
        'document_path',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'request_date' => 'date',
        'current_expiry_date' => 'date',
        'new_expiry_date' => 'date',
        'renewal_fee' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // حالات التجديد
    public const STATUSES = [
        'pending' => 'بانتظار التجديد',
        'processing' => 'جاري المعالجة',
        'renewed' => 'تم التجديد',
        'rejected' => 'مرفوض',
    ];

    // العلاقات
    public function bond(): BelongsTo
    {
        return $this->belongsTo(TenderBond::class);
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

    public function scopeRenewed($query)
    {
        return $query->where('status', 'renewed');
    }

    // Methods
    public function approve(User $user): void
    {
        $this->update([
            'status' => 'renewed',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        // تحديث الكفالة الأصلية
        $this->bond->update([
            'expiry_date' => $this->new_expiry_date,
            'renewal_count' => $this->bond->renewal_count + 1,
        ]);
    }

    public function getTotalCostAttribute(): float
    {
        return ($this->renewal_fee ?? 0) + ($this->commission_amount ?? 0);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
