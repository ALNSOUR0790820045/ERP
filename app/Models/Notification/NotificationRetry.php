<?php

namespace App\Models\Notification;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRetry extends Model
{
    protected $table = 'notification_retries';

    protected $fillable = [
        'queue_id',
        'attempt_number',
        'attempted_at',
        'result',
        'error_message',
        'error_code',
        'response',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'response' => 'array',
    ];

    // العلاقات
    public function queue(): BelongsTo
    {
        return $this->belongsTo(NotificationQueue::class, 'queue_id');
    }

    // هل نجحت المحاولة؟
    public function isSuccessful(): bool
    {
        return $this->result === 'success';
    }

    // تسجيل محاولة
    public static function record(int $queueId, string $result, ?string $error = null, ?array $response = null): self
    {
        $attempt = NotificationQueue::find($queueId)->retries()->max('attempt_number') ?? 0;

        return static::create([
            'queue_id' => $queueId,
            'attempt_number' => $attempt + 1,
            'attempted_at' => now(),
            'result' => $result,
            'error_message' => $error,
            'response' => $response,
        ]);
    }
}
