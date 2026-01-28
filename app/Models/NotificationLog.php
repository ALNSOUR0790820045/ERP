<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سجل الإشعارات
 * Notification Log
 */
class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'user_id',
        'channel',
        'status',
        'title',
        'body',
        'data',
        'error_message',
        'sent_at',
        'read_at',
        'clicked_at',
        'action_url',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    // الحالات
    const STATUSES = [
        'pending' => 'قيد الانتظار',
        'sent' => 'تم الإرسال',
        'delivered' => 'تم التوصيل',
        'read' => 'تمت القراءة',
        'failed' => 'فشل',
    ];

    // القنوات
    const CHANNELS = [
        'database' => 'قاعدة البيانات',
        'email' => 'بريد إلكتروني',
        'sms' => 'رسالة نصية',
        'push' => 'إشعار فوري',
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
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
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
            'sent' => 'info',
            'delivered' => 'success',
            'read' => 'success',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    public function getChannelLabelAttribute(): string
    {
        return self::CHANNELS[$this->channel] ?? $this->channel;
    }

    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }

    // Methods
    /**
     * تعليم كمقروء
     */
    public function markAsRead(): bool
    {
        if ($this->read_at) return true;
        
        $this->read_at = now();
        $this->status = 'read';
        return $this->save();
    }

    /**
     * تعليم كمنقور
     */
    public function markAsClicked(): bool
    {
        $this->clicked_at = now();
        return $this->markAsRead();
    }

    /**
     * الحصول على عدد غير المقروء
     */
    public static function getUnreadCount(int $userId): int
    {
        return static::forUser($userId)->unread()->count();
    }

    /**
     * الحصول على إشعارات المستخدم
     */
    public static function getForUser(int $userId, int $limit = 20): \Illuminate\Support\Collection
    {
        return static::forUser($userId)
            ->byChannel('database')
            ->recent()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * تعليم جميع إشعارات المستخدم كمقروءة
     */
    public static function markAllAsRead(int $userId): int
    {
        return static::forUser($userId)
            ->unread()
            ->update(['read_at' => now(), 'status' => 'read']);
    }
}
