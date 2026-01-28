<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditAlert extends Model
{
    protected $fillable = [
        'customer_credit_profile_id',
        'alert_type',
        'alert_message',
        'related_amount',
        'is_read',
        'is_actioned',
        'actioned_by',
        'actioned_at',
        'action_taken',
    ];

    protected $casts = [
        'related_amount' => 'decimal:2',
        'is_read' => 'boolean',
        'is_actioned' => 'boolean',
        'actioned_at' => 'datetime',
    ];

    public static array $alertTypeLabels = [
        'threshold_warning' => 'تحذير اقتراب الحد',
        'limit_exceeded' => 'تجاوز الحد',
        'overdue_payment' => 'تأخر السداد',
        'rating_downgrade' => 'تخفيض التصنيف',
        'payment_received' => 'استلام دفعة',
        'credit_review_due' => 'موعد المراجعة',
    ];

    public function creditProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerCreditProfile::class, 'customer_credit_profile_id');
    }

    public function actionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeUnactioned($query)
    {
        return $query->where('is_actioned', false);
    }

    public function markAsRead(): bool
    {
        $this->is_read = true;
        return $this->save();
    }

    public function takeAction(int $userId, string $action): bool
    {
        $this->is_actioned = true;
        $this->actioned_by = $userId;
        $this->actioned_at = now();
        $this->action_taken = $action;
        return $this->save();
    }
}
