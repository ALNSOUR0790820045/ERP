<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تفضيلات إشعارات المستخدم
 * User Notification Preference
 */
class UserNotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'email_enabled',
        'sms_enabled',
        'push_enabled',
        'database_enabled',
        'frequency',
        'quiet_hours_start',
        'quiet_hours_end',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'database_enabled' => 'boolean',
        'quiet_hours_start' => 'datetime:H:i',
        'quiet_hours_end' => 'datetime:H:i',
    ];

    // التكرار
    const FREQUENCIES = [
        'instant' => 'فوري',
        'hourly' => 'كل ساعة',
        'daily' => 'يومي',
        'weekly' => 'أسبوعي',
        'never' => 'أبداً',
    ];

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getFrequencyLabelAttribute(): string
    {
        return self::FREQUENCIES[$this->frequency] ?? $this->frequency;
    }

    // Methods
    /**
     * التحقق من القناة
     */
    public function isChannelEnabled(string $channel): bool
    {
        return match($channel) {
            'email' => $this->email_enabled,
            'sms' => $this->sms_enabled,
            'push' => $this->push_enabled,
            'database' => $this->database_enabled,
            default => false,
        };
    }

    /**
     * التحقق من وقت الهدوء
     */
    public function isInQuietHours(): bool
    {
        if (!$this->quiet_hours_start || !$this->quiet_hours_end) {
            return false;
        }

        $now = now()->format('H:i');
        $start = $this->quiet_hours_start;
        $end = $this->quiet_hours_end;

        if ($start <= $end) {
            return $now >= $start && $now <= $end;
        }
        
        return $now >= $start || $now <= $end;
    }

    /**
     * الحصول على تفضيلات المستخدم
     */
    public static function getForUser(int $userId, string $category): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId, 'category' => $category],
            [
                'email_enabled' => true,
                'sms_enabled' => false,
                'push_enabled' => true,
                'database_enabled' => true,
                'frequency' => 'instant',
            ]
        );
    }
}
