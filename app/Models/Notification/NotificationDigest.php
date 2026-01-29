<?php

namespace App\Models\Notification;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class NotificationDigest extends Model
{
    protected $table = 'notification_digests';

    protected $fillable = [
        'user_id',
        'digest_type',
        'period_start',
        'period_end',
        'notification_count',
        'notification_ids',
        'summary',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'notification_ids' => 'array',
        'summary' => 'array',
        'sent_at' => 'datetime',
    ];

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    public function scopeDaily(Builder $query): Builder
    {
        return $query->where('digest_type', 'daily');
    }

    public function scopeWeekly(Builder $query): Builder
    {
        return $query->where('digest_type', 'weekly');
    }

    public function scopeMonthly(Builder $query): Builder
    {
        return $query->where('digest_type', 'monthly');
    }

    // تحديد كمرسل
    public function markAsSent(): bool
    {
        return $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    // تحديد كفاشل
    public function markAsFailed(): bool
    {
        return $this->update(['status' => 'failed']);
    }

    // إنشاء ملخص يومي
    public static function createDaily(User $user, array $notificationIds, array $summary): self
    {
        return static::create([
            'user_id' => $user->id,
            'digest_type' => 'daily',
            'period_start' => now()->startOfDay(),
            'period_end' => now()->endOfDay(),
            'notification_count' => count($notificationIds),
            'notification_ids' => $notificationIds,
            'summary' => $summary,
            'status' => 'pending',
        ]);
    }

    // إنشاء ملخص أسبوعي
    public static function createWeekly(User $user, array $notificationIds, array $summary): self
    {
        return static::create([
            'user_id' => $user->id,
            'digest_type' => 'weekly',
            'period_start' => now()->startOfWeek(),
            'period_end' => now()->endOfWeek(),
            'notification_count' => count($notificationIds),
            'notification_ids' => $notificationIds,
            'summary' => $summary,
            'status' => 'pending',
        ]);
    }

    // تحقق إذا كان هناك ملخص موجود للفترة
    public static function existsForPeriod(int $userId, string $type, $periodStart): bool
    {
        return static::where('user_id', $userId)
            ->where('digest_type', $type)
            ->where('period_start', $periodStart)
            ->exists();
    }
}
