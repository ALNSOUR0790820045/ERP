<?php

namespace App\Models\DocumentManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Signature Request Model
 * طلبات التوقيع الإلكتروني
 */
class SignatureRequest extends Model
{
    protected $fillable = [
        'signable_type',
        'signable_id',
        'requester_id',
        'signer_id',
        'order',
        'status',
        'role',
        'message',
        'due_date',
        'sent_at',
        'viewed_at',
        'signed_at',
        'decline_reason',
        'access_token',
        'reminder_count',
        'last_reminder_at',
        'metadata',
    ];

    protected $casts = [
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'signed_at' => 'datetime',
        'last_reminder_at' => 'datetime',
        'metadata' => 'array',
        'order' => 'integer',
        'reminder_count' => 'integer',
    ];

    // Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_VIEWED = 'viewed';
    const STATUS_SIGNED = 'signed';
    const STATUS_DECLINED = 'declined';
    const STATUS_EXPIRED = 'expired';

    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', self::STATUS_SIGNED)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    // Helper Methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSigned(): bool
    {
        return $this->status === self::STATUS_SIGNED;
    }

    public function isOverdue(): bool
    {
        return !$this->isSigned() && $this->due_date && $this->due_date->isPast();
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function markAsViewed(): void
    {
        if (!$this->viewed_at) {
            $this->update([
                'status' => self::STATUS_VIEWED,
                'viewed_at' => now(),
            ]);
        }
    }

    public function markAsSigned(): void
    {
        $this->update([
            'status' => self::STATUS_SIGNED,
            'signed_at' => now(),
        ]);
    }

    public function decline(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_DECLINED,
            'decline_reason' => $reason,
        ]);
    }

    public function sendReminder(): void
    {
        $this->increment('reminder_count');
        $this->update(['last_reminder_at' => now()]);
    }
}
