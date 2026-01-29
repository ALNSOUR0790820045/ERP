<?php

namespace App\Models\Notification;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class EscalationLevel extends Model
{
    protected $table = 'escalation_levels';

    protected $fillable = [
        'rule_id',
        'level',
        'wait_hours',
        'escalate_to_type',
        'escalate_to_user_id',
        'escalate_to_role_id',
        'notification_template_id',
        'channels',
        'action_on_timeout',
        'is_active',
    ];

    protected $casts = [
        'channels' => 'array',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function rule(): BelongsTo
    {
        return $this->belongsTo(EscalationRule::class, 'rule_id');
    }

    public function escalateToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalate_to_user_id');
    }

    public function escalateToRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'escalate_to_role_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // الحصول على المستلمين
    public function getRecipients(): array
    {
        $recipients = [];

        if ($this->escalate_to_type === 'user' && $this->escalateToUser) {
            $recipients[] = $this->escalateToUser;
        } elseif ($this->escalate_to_type === 'role' && $this->escalateToRole) {
            $recipients = $this->escalateToRole->users ?? [];
        }

        return is_array($recipients) ? $recipients : $recipients->toArray();
    }

    // حساب وقت التصعيد التالي
    public function getNextEscalationTime(\DateTime $startTime): \DateTime
    {
        return (clone $startTime)->modify("+{$this->wait_hours} hours");
    }

    // هل يجب التصعيد بناءً على الوقت؟
    public function shouldEscalate(\DateTime $startTime): bool
    {
        return now() >= $this->getNextEscalationTime($startTime);
    }
}
