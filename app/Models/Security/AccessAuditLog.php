<?php

namespace App\Models\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AccessAuditLog extends Model
{
    protected $table = 'access_audit_logs';

    protected $fillable = [
        'user_id',
        'session_id',
        'action_type',
        'resource_type',
        'resource_id',
        'resource_name',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'status',
        'failure_reason',
        'metadata',
        'performed_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'performed_at' => 'datetime',
    ];

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action_type', $action);
    }

    public function scopeByResource(Builder $query, string $type, ?int $id = null): Builder
    {
        $query->where('resource_type', $type);
        if ($id) $query->where('resource_id', $id);
        return $query;
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeDenied(Builder $query): Builder
    {
        return $query->where('status', 'denied');
    }

    public function scopeInPeriod(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('performed_at', [$from, $to]);
    }

    // تسجيل إجراء
    public static function log(string $actionType, array $data = []): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'action_type' => $actionType,
            'resource_type' => $data['resource_type'] ?? null,
            'resource_id' => $data['resource_id'] ?? null,
            'resource_name' => $data['resource_name'] ?? null,
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'status' => $data['status'] ?? 'success',
            'failure_reason' => $data['failure_reason'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'performed_at' => now(),
        ]);
    }

    // تسجيل تسجيل الدخول
    public static function logLogin(User $user): self
    {
        return static::log('login', [
            'resource_type' => 'User',
            'resource_id' => $user->id,
            'resource_name' => $user->name,
        ]);
    }

    // تسجيل تسجيل الخروج
    public static function logLogout(User $user): self
    {
        return static::log('logout', [
            'resource_type' => 'User',
            'resource_id' => $user->id,
            'resource_name' => $user->name,
        ]);
    }

    // تسجيل وصول مرفوض
    public static function logAccessDenied(string $resource, string $reason): self
    {
        return static::log('access', [
            'resource_type' => $resource,
            'status' => 'denied',
            'failure_reason' => $reason,
        ]);
    }

    // الحصول على التغييرات
    public function getChanges(): array
    {
        $changes = [];
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];

        foreach ($new as $key => $value) {
            if (!isset($old[$key]) || $old[$key] !== $value) {
                $changes[$key] = [
                    'old' => $old[$key] ?? null,
                    'new' => $value,
                ];
            }
        }

        return $changes;
    }
}
