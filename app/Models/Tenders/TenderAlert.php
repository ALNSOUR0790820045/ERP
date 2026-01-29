<?php

namespace App\Models\Tenders;

use App\Models\Tender;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تنبيهات وتذكيرات العطاءات
 * Tender Alerts
 */
class TenderAlert extends Model
{
    protected $fillable = [
        'tender_id',
        'alert_type',
        'title_ar',
        'title_en',
        'message_ar',
        'message_en',
        'alert_date',
        'due_date',
        'days_before',
        'priority',
        'status',
        'recipients',
        'email_sent',
        'sms_sent',
        'system_notification',
        'created_by',
    ];

    protected $casts = [
        'alert_date' => 'datetime',
        'due_date' => 'datetime',
        'recipients' => 'array',
        'email_sent' => 'boolean',
        'sms_sent' => 'boolean',
        'system_notification' => 'boolean',
    ];

    // أنواع التنبيهات
    public const ALERT_TYPES = [
        'deadline_approaching' => 'موعد نهائي يقترب',
        'bond_expiry' => 'انتهاء كفالة',
        'site_visit_reminder' => 'تذكير بزيارة الموقع',
        'questions_deadline' => 'موعد الاستفسارات',
        'decision_required' => 'مطلوب قرار',
        'submission_reminder' => 'تذكير بالتقديم',
        'opening_notification' => 'إشعار فتح المظاريف',
        'award_tracking' => 'متابعة الإحالة',
        'bond_renewal_needed' => 'تجديد كفالة مطلوب',
        'document_missing' => 'وثيقة ناقصة',
        'action_required' => 'إجراء مطلوب',
    ];

    // مستويات الأولوية
    public const PRIORITIES = [
        'low' => 'منخفضة',
        'medium' => 'متوسطة',
        'high' => 'عالية',
        'urgent' => 'عاجلة',
    ];

    // الحالات
    public const STATUSES = [
        'pending' => 'معلق',
        'sent' => 'مرسل',
        'read' => 'مقروء',
        'dismissed' => 'مرفوض',
    ];

    // العلاقات
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnread($query)
    {
        return $query->whereIn('status', ['pending', 'sent']);
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today());
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereIn('status', ['pending', 'sent']);
    }

    // Methods
    public function send(): void
    {
        $this->update(['status' => 'sent']);

        // هنا يمكن إضافة منطق إرسال البريد والرسائل
    }

    public function markAsRead(): void
    {
        $this->update(['status' => 'read']);
    }

    public function dismiss(): void
    {
        $this->update(['status' => 'dismissed']);
    }

    // Static Methods
    public static function createDeadlineAlert(Tender $tender, int $daysBefore = 3): self
    {
        return self::create([
            'tender_id' => $tender->id,
            'alert_type' => 'deadline_approaching',
            'title_ar' => "موعد تقديم العطاء: {$tender->name_ar}",
            'title_en' => "Tender Deadline: {$tender->name_en}",
            'message_ar' => "يقترب موعد تقديم العطاء رقم {$tender->tender_number}",
            'alert_date' => $tender->submission_deadline->subDays($daysBefore),
            'due_date' => $tender->submission_deadline,
            'days_before' => $daysBefore,
            'priority' => $daysBefore <= 1 ? 'urgent' : 'high',
            'status' => 'pending',
        ]);
    }

    public static function createBondExpiryAlert(TenderBond $bond, int $daysBefore = 7): self
    {
        return self::create([
            'tender_id' => $bond->tender_id,
            'alert_type' => 'bond_expiry',
            'title_ar' => "انتهاء كفالة العطاء: {$bond->bond_number}",
            'message_ar' => "ستنتهي كفالة العطاء رقم {$bond->bond_number} بتاريخ {$bond->expiry_date->format('Y-m-d')}",
            'alert_date' => $bond->expiry_date->subDays($daysBefore),
            'due_date' => $bond->expiry_date,
            'days_before' => $daysBefore,
            'priority' => 'high',
            'status' => 'pending',
        ]);
    }

    // Accessors
    public function getAlertTypeLabelAttribute(): string
    {
        return self::ALERT_TYPES[$this->alert_type] ?? $this->alert_type;
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getTitleAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->title_ar : ($this->title_en ?? $this->title_ar);
    }

    public function getMessageAttribute(): ?string
    {
        return app()->getLocale() === 'ar' ? $this->message_ar : ($this->message_en ?? $this->message_ar);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date < now() && in_array($this->status, ['pending', 'sent']);
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }
        return now()->diffInDays($this->due_date, false);
    }
}
