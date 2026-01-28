<?php

namespace App\Models\DocumentManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Electronic Signature Model
 * التوقيع الإلكتروني
 */
class ElectronicSignature extends Model
{
    protected $fillable = [
        'signable_type',
        'signable_id',
        'user_id',
        'signature_type',
        'signature_data',
        'signature_image',
        'certificate_id',
        'ip_address',
        'user_agent',
        'location',
        'signed_at',
        'reason',
        'status',
        'hash',
        'verification_code',
        'is_verified',
        'verified_at',
        'metadata',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'verified_at' => 'datetime',
        'is_verified' => 'boolean',
        'location' => 'array',
        'metadata' => 'array',
    ];

    // Signature Type Constants
    const TYPE_SIMPLE = 'simple';
    const TYPE_ADVANCED = 'advanced';
    const TYPE_QUALIFIED = 'qualified';
    const TYPE_DIGITAL = 'digital';

    // Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_SIGNED = 'signed';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REVOKED = 'revoked';
    const STATUS_EXPIRED = 'expired';

    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeSigned($query)
    {
        return $query->whereIn('status', [self::STATUS_SIGNED, self::STATUS_VERIFIED]);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // Helper Methods
    public function isSigned(): bool
    {
        return in_array($this->status, [self::STATUS_SIGNED, self::STATUS_VERIFIED]);
    }

    public function isVerified(): bool
    {
        return $this->is_verified === true;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function sign(string $signatureData, string $reason = null): void
    {
        $this->update([
            'signature_data' => $signatureData,
            'signed_at' => now(),
            'reason' => $reason,
            'status' => self::STATUS_SIGNED,
            'hash' => $this->generateHash($signatureData),
            'verification_code' => $this->generateVerificationCode(),
        ]);
    }

    public function verify(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'status' => self::STATUS_VERIFIED,
        ]);
    }

    public function revoke(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_REVOKED,
            'metadata' => array_merge($this->metadata ?? [], [
                'revoked_at' => now()->toIso8601String(),
                'revoke_reason' => $reason,
            ]),
        ]);
    }

    protected function generateHash(string $data): string
    {
        return hash('sha256', $data . $this->user_id . $this->signable_type . $this->signable_id . now()->timestamp);
    }

    protected function generateVerificationCode(): string
    {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    }

    public function validateHash(): bool
    {
        if (!$this->signature_data || !$this->hash) {
            return false;
        }

        $expectedHash = hash('sha256', $this->signature_data . $this->user_id . $this->signable_type . $this->signable_id . $this->signed_at->timestamp);
        return hash_equals($this->hash, $expectedHash);
    }

    public static function createForDocument(Model $document, User $user, string $type = self::TYPE_SIMPLE, array $meta = []): self
    {
        return static::create([
            'signable_type' => get_class($document),
            'signable_id' => $document->id,
            'user_id' => $user->id,
            'signature_type' => $type,
            'status' => self::STATUS_PENDING,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $meta,
        ]);
    }

    public function getSignatureTypeLabel(): string
    {
        return match ($this->signature_type) {
            self::TYPE_SIMPLE => 'توقيع بسيط',
            self::TYPE_ADVANCED => 'توقيع متقدم',
            self::TYPE_QUALIFIED => 'توقيع مؤهل',
            self::TYPE_DIGITAL => 'توقيع رقمي',
            default => 'توقيع',
        };
    }

    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_VERIFIED => 'success',
            self::STATUS_SIGNED => 'primary',
            self::STATUS_PENDING => 'warning',
            self::STATUS_REVOKED => 'danger',
            self::STATUS_EXPIRED => 'secondary',
            default => 'info',
        };
    }
}
