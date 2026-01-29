<?php

namespace App\Models\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class LoginAttempt extends Model
{
    protected $table = 'login_attempts';

    protected $fillable = [
        'username',
        'user_id',
        'ip_address',
        'user_agent',
        'status',
        'failure_reason',
        'location',
        'device_type',
        'browser',
        'latitude',
        'longitude',
        'attempted_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('status', 'blocked');
    }

    public function scopeRecent(Builder $query, int $minutes = 30): Builder
    {
        return $query->where('attempted_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeForUser(Builder $query, string $username): Builder
    {
        return $query->where('username', $username);
    }

    public function scopeFromIp(Builder $query, string $ip): Builder
    {
        return $query->where('ip_address', $ip);
    }

    // تسجيل محاولة تسجيل دخول
    public static function record(array $data): self
    {
        $userAgent = request()->userAgent();
        
        return static::create([
            'username' => $data['username'],
            'user_id' => $data['user_id'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => $userAgent,
            'status' => $data['status'],
            'failure_reason' => $data['failure_reason'] ?? null,
            'location' => $data['location'] ?? null,
            'device_type' => static::detectDeviceType($userAgent),
            'browser' => static::detectBrowser($userAgent),
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'attempted_at' => now(),
        ]);
    }

    // عدد المحاولات الفاشلة
    public static function getFailedAttemptsCount(string $username, int $minutes = 30): int
    {
        return static::forUser($username)
            ->failed()
            ->recent($minutes)
            ->count();
    }

    // التحقق من الحظر
    public static function isBlocked(string $username, int $threshold = 5, int $minutes = 30): bool
    {
        return static::getFailedAttemptsCount($username, $minutes) >= $threshold;
    }

    // كشف نوع الجهاز
    protected static function detectDeviceType(?string $userAgent): ?string
    {
        if (!$userAgent) return null;
        
        if (preg_match('/mobile/i', $userAgent)) return 'mobile';
        if (preg_match('/tablet/i', $userAgent)) return 'tablet';
        return 'desktop';
    }

    // كشف المتصفح
    protected static function detectBrowser(?string $userAgent): ?string
    {
        if (!$userAgent) return null;
        
        if (preg_match('/Chrome/i', $userAgent)) return 'Chrome';
        if (preg_match('/Firefox/i', $userAgent)) return 'Firefox';
        if (preg_match('/Safari/i', $userAgent)) return 'Safari';
        if (preg_match('/Edge/i', $userAgent)) return 'Edge';
        if (preg_match('/MSIE|Trident/i', $userAgent)) return 'Internet Explorer';
        
        return 'Unknown';
    }
}
