<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * التذكيرات
 * Reminder
 */
class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'remind_at',
        'repeat_type',
        'repeat_interval',
        'repeat_until',
        'last_triggered_at',
        'is_active',
        'priority',
        'notifiable_type',
        'notifiable_id',
        'channels',
        'metadata',
    ];

    protected $casts = [
        'remind_at' => 'datetime',
        'repeat_until' => 'date',
        'last_triggered_at' => 'datetime',
        'is_active' => 'boolean',
        'channels' => 'array',
        'metadata' => 'array',
    ];

    // أنواع التكرار
    const REPEAT_TYPES = [
        'none' => 'مرة واحدة',
        'daily' => 'يومي',
        'weekly' => 'أسبوعي',
        'monthly' => 'شهري',
        'yearly' => 'سنوي',
        'custom' => 'مخصص',
    ];

    // الأولويات
    const PRIORITIES = [
        'low' => 'منخفضة',
        'normal' => 'عادية',
        'high' => 'عالية',
    ];

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->active()->where('remind_at', '<=', now());
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->active()
            ->where('remind_at', '>=', now())
            ->where('remind_at', '<=', now()->addDays($days));
    }

    // Accessors
    public function getRepeatTypeLabelAttribute(): string
    {
        return self::REPEAT_TYPES[$this->repeat_type] ?? $this->repeat_type;
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low' => 'gray',
            'normal' => 'info',
            'high' => 'warning',
            default => 'gray',
        };
    }

    public function getIsDueAttribute(): bool
    {
        return $this->is_active && $this->remind_at <= now();
    }

    // Methods
    /**
     * تشغيل التذكير
     */
    public function trigger(): bool
    {
        // إرسال الإشعار
        $template = NotificationTemplate::findByEvent('reminder');
        
        if ($template) {
            $template->send($this->user, [
                'title' => $this->title,
                'description' => $this->description,
                'remind_at' => $this->remind_at->format('Y-m-d H:i'),
            ]);
        }

        $this->last_triggered_at = now();

        // حساب الموعد التالي
        if ($this->repeat_type !== 'none') {
            $this->calculateNextReminder();
        } else {
            $this->is_active = false;
        }

        return $this->save();
    }

    /**
     * حساب التذكير التالي
     */
    protected function calculateNextReminder(): void
    {
        $next = match($this->repeat_type) {
            'daily' => $this->remind_at->addDay(),
            'weekly' => $this->remind_at->addWeek(),
            'monthly' => $this->remind_at->addMonth(),
            'yearly' => $this->remind_at->addYear(),
            'custom' => $this->remind_at->addDays($this->repeat_interval ?? 1),
            default => null,
        };

        if ($next && (!$this->repeat_until || $next <= $this->repeat_until)) {
            $this->remind_at = $next;
        } else {
            $this->is_active = false;
        }
    }

    /**
     * إيقاف التذكير
     */
    public function stop(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    /**
     * تأجيل التذكير
     */
    public function snooze(int $minutes = 15): bool
    {
        $this->remind_at = now()->addMinutes($minutes);
        return $this->save();
    }

    /**
     * معالجة التذكيرات المستحقة
     */
    public static function processDue(): int
    {
        $reminders = static::due()->with('user')->get();
        $count = 0;

        foreach ($reminders as $reminder) {
            if ($reminder->trigger()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * إنشاء تذكير سريع
     */
    public static function quick(
        int $userId,
        string $title,
        \Carbon\Carbon $remindAt,
        ?string $description = null
    ): self {
        return static::create([
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'remind_at' => $remindAt,
            'repeat_type' => 'none',
            'is_active' => true,
            'priority' => 'normal',
            'channels' => ['database', 'push'],
        ]);
    }
}
