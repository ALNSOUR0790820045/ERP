<?php

namespace App\Models\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class UserLockout extends Model
{
    protected $table = 'user_lockouts';

    protected $fillable = [
        'user_id',
        'lockout_type',
        'reason',
        'locked_at',
        'locked_until',
        'is_permanent',
        'locked_by',
        'unlocked_at',
        'unlocked_by',
        'unlock_reason',
        'ip_address',
        'metadata',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'locked_until' => 'datetime',
        'unlocked_at' => 'datetime',
        'is_permanent' => 'boolean',
        'metadata' => 'array',
    ];

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function unlockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('unlocked_at')
            ->where(function ($q) {
                $q->where('is_permanent', true)
                  ->orWhere('locked_until', '>', now());
            });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNull('unlocked_at')
            ->where('is_permanent', false)
            ->where('locked_until', '<=', now());
    }

    // التحقق من الحظر
    public function isActive(): bool
    {
        if ($this->unlocked_at) return false;
        if ($this->is_permanent) return true;
        return $this->locked_until && $this->locked_until->isFuture();
    }

    // إلغاء الحظر
    public function unlock(int $unlockedBy, string $reason): bool
    {
        return $this->update([
            'unlocked_at' => now(),
            'unlocked_by' => $unlockedBy,
            'unlock_reason' => $reason,
        ]);
    }

    // إنشاء حظر جديد
    public static function lockUser(User $user, string $type, string $reason, ?int $durationMinutes = null, ?int $lockedBy = null): self
    {
        return static::create([
            'user_id' => $user->id,
            'lockout_type' => $type,
            'reason' => $reason,
            'locked_at' => now(),
            'locked_until' => $durationMinutes ? now()->addMinutes($durationMinutes) : null,
            'is_permanent' => $durationMinutes === null,
            'locked_by' => $lockedBy,
            'ip_address' => request()->ip(),
        ]);
    }

    // الحصول على الحظر النشط للمستخدم
    public static function getActiveLockout(int $userId): ?self
    {
        return static::where('user_id', $userId)->active()->latest('locked_at')->first();
    }

    // فحص إذا كان المستخدم محظوراً
    public static function isUserLocked(int $userId): bool
    {
        return static::where('user_id', $userId)->active()->exists();
    }
}
