<?php

namespace App\Models\DocumentManagement;

use App\Models\Transmittal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Transmittal Acknowledgment Model
 * إثبات استلام الرسائل
 */
class TransmittalAcknowledgment extends Model
{
    protected $fillable = [
        'transmittal_id',
        'recipient_id',
        'recipient_email',
        'recipient_name',
        'status',
        'sent_at',
        'received_at',
        'acknowledged_at',
        'acknowledgment_notes',
        'acknowledgment_method',
        'ip_address',
        'signature_id',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_RECEIVED = 'received';
    const STATUS_ACKNOWLEDGED = 'acknowledged';
    const STATUS_FAILED = 'failed';

    // Acknowledgment Methods
    const METHOD_EMAIL = 'email';
    const METHOD_WEB = 'web';
    const METHOD_MOBILE = 'mobile';
    const METHOD_SIGNATURE = 'signature';

    public function transmittal(): BelongsTo
    {
        return $this->belongsTo(Transmittal::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function signature(): BelongsTo
    {
        return $this->belongsTo(ElectronicSignature::class, 'signature_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAcknowledged($query)
    {
        return $query->where('status', self::STATUS_ACKNOWLEDGED);
    }

    public function scopeUnacknowledged($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_SENT,
            self::STATUS_RECEIVED,
        ]);
    }

    public function scopeOverdue($query, int $days = 3)
    {
        return $query->unacknowledged()
            ->where('sent_at', '<', now()->subDays($days));
    }

    // Helper Methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAcknowledged(): bool
    {
        return $this->status === self::STATUS_ACKNOWLEDGED;
    }

    public function isOverdue(int $days = 3): bool
    {
        return !$this->isAcknowledged() 
            && $this->sent_at 
            && $this->sent_at->addDays($days)->isPast();
    }

    public function getRecipientDisplayName(): string
    {
        if ($this->recipient) {
            return $this->recipient->name;
        }
        return $this->recipient_name ?? $this->recipient_email ?? 'Unknown';
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function markAsReceived(?string $ipAddress = null): void
    {
        $this->update([
            'status' => self::STATUS_RECEIVED,
            'received_at' => now(),
            'ip_address' => $ipAddress,
        ]);
    }

    public function acknowledge(string $notes = null, string $method = self::METHOD_WEB): void
    {
        $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
            'acknowledgment_notes' => $notes,
            'acknowledgment_method' => $method,
        ]);
    }

    public function acknowledgeWithSignature(int $signatureId, string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
            'acknowledgment_notes' => $notes,
            'acknowledgment_method' => self::METHOD_SIGNATURE,
            'signature_id' => $signatureId,
        ]);
    }

    public function getDaysToAcknowledge(): ?int
    {
        if (!$this->sent_at || !$this->acknowledged_at) {
            return null;
        }
        return $this->sent_at->diffInDays($this->acknowledged_at);
    }

    public static function createForTransmittal(Transmittal $transmittal, array $recipients): array
    {
        $acknowledgments = [];

        foreach ($recipients as $recipient) {
            $data = [
                'transmittal_id' => $transmittal->id,
                'status' => self::STATUS_PENDING,
            ];

            if ($recipient instanceof User) {
                $data['recipient_id'] = $recipient->id;
                $data['recipient_name'] = $recipient->name;
                $data['recipient_email'] = $recipient->email;
            } elseif (is_array($recipient)) {
                $data = array_merge($data, $recipient);
            } else {
                $data['recipient_email'] = $recipient;
            }

            $acknowledgments[] = static::create($data);
        }

        return $acknowledgments;
    }
}
