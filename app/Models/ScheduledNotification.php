<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * الإشعارات المجدولة
 * Scheduled Notification
 */
class ScheduledNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'user_id',
        'scheduled_at',
        'data',
        'status',
        'sent_at',
        'cancelled_at',
        'error_message',
        'retry_count',
        'max_retries',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'data' => 'array',
    ];

    // الحالات
    const STATUSES = [
        'pending' => 'قيد الانتظار',
        'sent' => 'تم الإرسال',
        'cancelled' => 'ملغى',
        'failed' => 'فشل',
    ];

    // العلاقات
    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDue($query)
    {
        return $query->pending()->where('scheduled_at', '<=', now());
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'sent' => 'success',
            'cancelled' => 'gray',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    // Methods
    /**
     * إلغاء الإشعار
     */
    public function cancel(): bool
    {
        if ($this->status !== 'pending') return false;
        
        $this->status = 'cancelled';
        $this->cancelled_at = now();
        return $this->save();
    }

    /**
     * إرسال الإشعار
     */
    public function send(): bool
    {
        if ($this->status !== 'pending') return false;
        
        try {
            $this->template->send($this->user, $this->data ?? []);
            
            $this->status = 'sent';
            $this->sent_at = now();
            return $this->save();
        } catch (\Exception $e) {
            $this->retry_count++;
            $this->error_message = $e->getMessage();
            
            if ($this->retry_count >= $this->max_retries) {
                $this->status = 'failed';
            }
            
            $this->save();
            return false;
        }
    }

    /**
     * معالجة الإشعارات المجدولة
     */
    public static function processDue(): int
    {
        $notifications = static::due()->with(['template', 'user'])->get();
        $count = 0;

        foreach ($notifications as $notification) {
            if ($notification->send()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * جدولة إشعار
     */
    public static function schedule(
        int $templateId,
        int $userId,
        \Carbon\Carbon $scheduledAt,
        array $data = []
    ): self {
        return static::create([
            'template_id' => $templateId,
            'user_id' => $userId,
            'scheduled_at' => $scheduledAt,
            'data' => $data,
            'status' => 'pending',
            'retry_count' => 0,
            'max_retries' => 3,
        ]);
    }
}
