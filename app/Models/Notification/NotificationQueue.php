<?php

namespace App\Models\Notification;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class NotificationQueue extends Model
{
    protected $table = 'notification_queue';

    protected $fillable = [
        'user_id',
        'template_id',
        'channel',
        'recipient',
        'subject',
        'body',
        'data',
        'priority',
        'status',
        'scheduled_at',
        'sent_at',
        'retry_count',
        'next_retry_at',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'data' => 'array',
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function retries(): HasMany
    {
        return $this->hasMany(NotificationRetry::class, 'queue_id');
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', 'processing');
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeReadyToSend(Builder $query): Builder
    {
        return $query->pending()
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '<=', now());
            });
    }

    public function scopeReadyForRetry(Builder $query): Builder
    {
        return $query->failed()
            ->where('retry_count', '<', 3)
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                  ->orWhere('next_retry_at', '<=', now());
            });
    }

    public function scopeByChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->where('priority', '<=', 3);
    }

    // بدء المعالجة
    public function startProcessing(): bool
    {
        return $this->update(['status' => 'processing']);
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
    public function markAsFailed(string $error): bool
    {
        $this->retries()->create([
            'attempt_number' => $this->retry_count + 1,
            'attempted_at' => now(),
            'result' => 'failed',
            'error_message' => $error,
        ]);

        return $this->update([
            'status' => 'failed',
            'retry_count' => $this->retry_count + 1,
            'next_retry_at' => now()->addMinutes(pow(2, $this->retry_count) * 5), // Exponential backoff
            'error_message' => $error,
        ]);
    }

    // إلغاء الإشعار
    public function cancel(): bool
    {
        return $this->update(['status' => 'cancelled']);
    }

    // إعادة المحاولة
    public function retry(): bool
    {
        return $this->update([
            'status' => 'pending',
            'next_retry_at' => null,
        ]);
    }

    // إضافة إشعار للطابور
    public static function queue(array $data): self
    {
        return static::create([
            'user_id' => $data['user_id'] ?? null,
            'template_id' => $data['template_id'] ?? null,
            'channel' => $data['channel'],
            'recipient' => $data['recipient'],
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'],
            'data' => $data['data'] ?? null,
            'priority' => $data['priority'] ?? 5,
            'status' => 'pending',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }
}
