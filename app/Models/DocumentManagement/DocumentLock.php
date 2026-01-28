<?php

namespace App\Models\DocumentManagement;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Document Lock Model
 * قفل المستندات (Check-in/Check-out)
 */
class DocumentLock extends Model
{
    protected $fillable = [
        'document_id',
        'user_id',
        'lock_type',
        'reason',
        'locked_at',
        'expires_at',
        'unlocked_at',
        'unlocked_by',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'expires_at' => 'datetime',
        'unlocked_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    // Lock Types
    const TYPE_EDIT = 'edit';
    const TYPE_REVIEW = 'review';
    const TYPE_APPROVAL = 'approval';
    const TYPE_EXCLUSIVE = 'exclusive';

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unlockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDocument($query, int $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    public function scopeExpired($query)
    {
        return $query->active()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function unlock(?User $unlockedBy = null): void
    {
        $this->update([
            'is_active' => false,
            'unlocked_at' => now(),
            'unlocked_by' => $unlockedBy?->id,
        ]);

        // Update document lock status
        $this->document?->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
            'lock_type' => null,
        ]);
    }

    public function extend(int $hours = 2): void
    {
        $newExpiry = $this->expires_at ? $this->expires_at->addHours($hours) : now()->addHours($hours);
        $this->update(['expires_at' => $newExpiry]);
    }

    public static function checkOut(Document $document, User $user, string $type = self::TYPE_EDIT, int $hours = 8): self
    {
        // Release any existing locks
        static::active()->forDocument($document->id)->update(['is_active' => false, 'unlocked_at' => now()]);

        // Create new lock
        $lock = static::create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'lock_type' => $type,
            'locked_at' => now(),
            'expires_at' => now()->addHours($hours),
            'is_active' => true,
        ]);

        // Update document
        $document->update([
            'is_locked' => true,
            'locked_by' => $user->id,
            'locked_at' => now(),
            'lock_type' => $type,
        ]);

        return $lock;
    }

    public static function checkIn(Document $document, ?User $user = null): void
    {
        $lock = static::active()->forDocument($document->id)->first();
        
        if ($lock) {
            $lock->unlock($user);
        }
    }

    public static function isLocked(Document $document): bool
    {
        return static::active()->forDocument($document->id)->exists();
    }

    public static function getActiveLock(Document $document): ?self
    {
        return static::active()->forDocument($document->id)->first();
    }

    public static function canEdit(Document $document, User $user): bool
    {
        $lock = static::getActiveLock($document);
        
        if (!$lock) {
            return true;
        }

        return $lock->user_id === $user->id;
    }

    public static function releaseExpiredLocks(): int
    {
        $expired = static::expired()->get();
        
        foreach ($expired as $lock) {
            $lock->unlock();
        }

        return $expired->count();
    }
}
