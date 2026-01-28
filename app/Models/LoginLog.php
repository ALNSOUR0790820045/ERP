<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'login_at',
        'logout_at',
        'ip_address',
        'device',
        'browser',
        'platform',
        'status',
        'failure_reason',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'success' => 'ناجح',
            'failed' => 'فاشل',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'success' => 'success',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    public function getDuration(): ?string
    {
        if (!$this->logout_at) {
            return null;
        }

        $diff = $this->login_at->diff($this->logout_at);
        
        if ($diff->h > 0) {
            return $diff->format('%h ساعة %i دقيقة');
        }
        
        return $diff->format('%i دقيقة');
    }

    public static function recordLogin(User $user, bool $success = true, ?string $failureReason = null): self
    {
        $agent = request()->userAgent();
        
        return self::create([
            'user_id' => $user->id,
            'login_at' => now(),
            'ip_address' => request()->ip(),
            'device' => self::parseDevice($agent),
            'browser' => self::parseBrowser($agent),
            'platform' => self::parsePlatform($agent),
            'status' => $success ? 'success' : 'failed',
            'failure_reason' => $failureReason,
        ]);
    }

    public static function recordLogout(User $user): void
    {
        self::where('user_id', $user->id)
            ->whereNull('logout_at')
            ->latest('login_at')
            ->first()
            ?->update(['logout_at' => now()]);
    }

    private static function parseDevice(string $agent): string
    {
        if (str_contains($agent, 'Mobile')) return 'Mobile';
        if (str_contains($agent, 'Tablet')) return 'Tablet';
        return 'Desktop';
    }

    private static function parseBrowser(string $agent): string
    {
        if (str_contains($agent, 'Chrome')) return 'Chrome';
        if (str_contains($agent, 'Firefox')) return 'Firefox';
        if (str_contains($agent, 'Safari')) return 'Safari';
        if (str_contains($agent, 'Edge')) return 'Edge';
        return 'Other';
    }

    private static function parsePlatform(string $agent): string
    {
        if (str_contains($agent, 'Windows')) return 'Windows';
        if (str_contains($agent, 'Mac')) return 'macOS';
        if (str_contains($agent, 'Linux')) return 'Linux';
        if (str_contains($agent, 'Android')) return 'Android';
        if (str_contains($agent, 'iOS')) return 'iOS';
        return 'Other';
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
