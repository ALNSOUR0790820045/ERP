<?php

namespace App\Models\Notification;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Webhook extends Model
{
    protected $table = 'webhooks';

    protected $fillable = [
        'code',
        'name',
        'description',
        'url',
        'method',
        'headers',
        'events',
        'secret_key',
        'signature_type',
        'verify_ssl',
        'timeout_seconds',
        'max_retries',
        'status',
        'consecutive_failures',
        'last_triggered_at',
        'last_success_at',
        'created_by',
    ];

    protected $casts = [
        'headers' => 'array',
        'events' => 'array',
        'verify_ssl' => 'boolean',
        'last_triggered_at' => 'datetime',
        'last_success_at' => 'datetime',
    ];

    protected $hidden = [
        'secret_key',
    ];

    // العلاقات
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->whereJsonContains('events', $event);
    }

    // التحقق من الحدث
    public function handlesEvent(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }

    // إرسال webhook
    public function trigger(string $eventType, array $payload): WebhookLog
    {
        $log = $this->logs()->create([
            'event_type' => $eventType,
            'payload' => $payload,
            'status' => 'pending',
            'triggered_at' => now(),
        ]);

        $this->update(['last_triggered_at' => now()]);

        return $log;
    }

    // تسجيل نجاح
    public function recordSuccess(): void
    {
        $this->update([
            'consecutive_failures' => 0,
            'last_success_at' => now(),
            'status' => 'active',
        ]);
    }

    // تسجيل فشل
    public function recordFailure(): void
    {
        $failures = $this->consecutive_failures + 1;
        $status = $failures >= 5 ? 'failed' : $this->status;

        $this->update([
            'consecutive_failures' => $failures,
            'status' => $status,
        ]);
    }

    // توليد التوقيع
    public function generateSignature(array $payload): ?string
    {
        if (!$this->secret_key || $this->signature_type === 'none') {
            return null;
        }

        $data = json_encode($payload);
        $algorithm = match ($this->signature_type) {
            'hmac_sha256' => 'sha256',
            'hmac_sha512' => 'sha512',
            default => 'sha256',
        };

        return hash_hmac($algorithm, $data, $this->secret_key);
    }

    // إنشاء رمز سري جديد
    public function regenerateSecret(): string
    {
        $secret = Str::random(64);
        $this->update(['secret_key' => $secret]);
        return $secret;
    }

    // إيقاف مؤقت
    public function pause(): bool
    {
        return $this->update(['status' => 'paused']);
    }

    // إعادة التفعيل
    public function resume(): bool
    {
        return $this->update([
            'status' => 'active',
            'consecutive_failures' => 0,
        ]);
    }

    // تعطيل
    public function disable(): bool
    {
        return $this->update(['status' => 'disabled']);
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = 'WH-' . Str::upper(Str::random(8));
            }
            if (empty($model->secret_key)) {
                $model->secret_key = Str::random(64);
            }
        });
    }
}
