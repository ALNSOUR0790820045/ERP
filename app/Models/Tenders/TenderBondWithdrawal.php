<?php

namespace App\Models\Tenders;

use App\Models\TenderBond;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سحب الكفالات
 * Tender Bond Withdrawals
 */
class TenderBondWithdrawal extends Model
{
    protected $fillable = [
        'bond_id',
        'withdrawal_reason',
        'request_date',
        'withdrawal_date',
        'release_letter_number',
        'release_letter_date',
        'release_letter_path',
        'original_bond_path',
        'status',
        'refund_amount',
        'refund_date',
        'requested_by',
        'processed_by',
        'notes',
    ];

    protected $casts = [
        'request_date' => 'date',
        'withdrawal_date' => 'date',
        'release_letter_date' => 'date',
        'refund_date' => 'date',
        'refund_amount' => 'decimal:2',
    ];

    // أسباب السحب
    public const WITHDRAWAL_REASONS = [
        'tender_won' => 'فوز بالعطاء',
        'tender_lost' => 'خسارة العطاء',
        'tender_cancelled' => 'إلغاء العطاء',
        'expired' => 'انتهاء الصلاحية',
        'replaced' => 'استبدال بكفالة جديدة',
        'released' => 'إفراج من المالك',
        'other' => 'أخرى',
    ];

    // حالات السحب
    public const STATUSES = [
        'pending' => 'بانتظار السحب',
        'in_progress' => 'جاري السحب',
        'withdrawn' => 'تم السحب',
        'returned' => 'تم الإرجاع للبنك',
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

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeWithdrawn($query)
    {
        return $query->where('status', 'withdrawn');
    }

    public function scopeByReason($query, $reason)
    {
        return $query->where('withdrawal_reason', $reason);
    }

    // Methods
    public function process(User $user): void
    {
        $this->update([
            'status' => 'in_progress',
            'processed_by' => $user->id,
        ]);
    }

    public function complete(User $user, ?string $date = null): void
    {
        $this->update([
            'status' => 'withdrawn',
            'withdrawal_date' => $date ?? now(),
            'processed_by' => $user->id,
        ]);

        // تحديث الكفالة
        $this->bond->update([
            'is_withdrawn' => true,
            'withdrawn_at' => now(),
            'status' => 'withdrawn',
        ]);
    }

    public function markAsReturned(User $user): void
    {
        $this->update([
            'status' => 'returned',
            'processed_by' => $user->id,
        ]);
    }

    // Accessors
    public function getWithdrawalReasonLabelAttribute(): string
    {
        return self::WITHDRAWAL_REASONS[$this->withdrawal_reason] ?? $this->withdrawal_reason;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getIsCompletedAttribute(): bool
    {
        return in_array($this->status, ['withdrawn', 'returned']);
    }
}
