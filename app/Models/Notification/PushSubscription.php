<?php

namespace App\Models\Notification;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class PushSubscription extends Model
{
    protected $table = 'push_subscriptions';

    protected $fillable = [
        'user_id',
        'endpoint',
        'p256dh_key',
        'auth_key',
        'device_type',
        'device_name',
        'browser',
        'os',
        'subscribed_at',
        'last_used_at',
        'is_active',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'p256dh_key',
        'auth_key',
    ];

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDevice(Builder $query, string $type): Builder
    {
        return $query->where('device_type', $type);
    }

    // تحديث آخر استخدام
    public function recordUsage(): bool
    {
        return $this->update(['last_used_at' => now()]);
    }

    // إلغاء الاشتراك
    public function unsubscribe(): bool
    {
        return $this->update(['is_active' => false]);
    }

    // إعادة الاشتراك
    public function resubscribe(): bool
    {
        return $this->update([
            'is_active' => true,
            'subscribed_at' => now(),
        ]);
    }

    // الحصول على مفاتيح VAPID
    public function getVapidKeys(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'keys' => [
                'p256dh' => $this->p256dh_key,
                'auth' => $this->auth_key,
            ],
        ];
    }

    // البحث بـ endpoint
    public static function findByEndpoint(string $endpoint): ?self
    {
        return static::where('endpoint', $endpoint)->first();
    }

    // إنشاء أو تحديث اشتراك
    public static function subscribe(int $userId, array $subscription, array $deviceInfo = []): self
    {
        $endpoint = $subscription['endpoint'];

        return static::updateOrCreate(
            ['endpoint' => $endpoint],
            [
                'user_id' => $userId,
                'p256dh_key' => $subscription['keys']['p256dh'] ?? null,
                'auth_key' => $subscription['keys']['auth'] ?? null,
                'device_type' => $deviceInfo['device_type'] ?? null,
                'device_name' => $deviceInfo['device_name'] ?? null,
                'browser' => $deviceInfo['browser'] ?? null,
                'os' => $deviceInfo['os'] ?? null,
                'subscribed_at' => now(),
                'is_active' => true,
            ]
        );
    }

    // إلغاء الاشتراكات القديمة
    public static function cleanupInactive(int $days = 90): int
    {
        return static::where('is_active', true)
            ->where(function ($q) use ($days) {
                $q->where('last_used_at', '<', now()->subDays($days))
                  ->orWhereNull('last_used_at');
            })
            ->update(['is_active' => false]);
    }
}
