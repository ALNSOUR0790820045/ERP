<?php

namespace App\Models\Tenders;

use App\Models\Company;
use App\Models\Tender;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نموذج الاعتراضات
 * يمثل اعتراضات المناقصين على قرارات لجنة الشراء حسب نظام المشتريات الحكومية الأردني
 */
class TenderObjection extends Model
{
    protected $fillable = [
        'tender_id',
        'objector_id',
        'objector_name',
        'objector_contact',
        'objector_phone',
        'objector_email',
        'objection_type',
        'objection_number',
        'objection_date',
        'objection_subject',
        'objection_details',
        'legal_basis',
        'requested_action',
        'attachments',
        'status',
        'committee_decision',
        'decision_date',
        'decided_by',
        'decision_justification',
        'is_escalated_to_complaints',
        'escalation_date',
        'complaint_fee',
        'complaint_fee_paid',
        'complaints_committee_decision',
        'complaints_decision_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'objection_date' => 'date',
        'decision_date' => 'date',
        'escalation_date' => 'date',
        'complaints_decision_date' => 'date',
        'is_escalated_to_complaints' => 'boolean',
        'complaint_fee_paid' => 'boolean',
        'complaint_fee' => 'decimal:2',
    ];

    // ==========================================
    // الثوابت - أنواع الاعتراضات
    // ==========================================

    const TYPE_PRELIMINARY_AWARD = 'preliminary_award';
    const TYPE_DOCUMENTS = 'documents';
    const TYPE_EVALUATION = 'evaluation';
    const TYPE_QUALIFICATION = 'qualification';
    const TYPE_PROCEDURE = 'procedure';
    const TYPE_OTHER = 'other';

    /**
     * أنواع الاعتراضات
     */
    public static function getObjectionTypes(): array
    {
        return [
            self::TYPE_PRELIMINARY_AWARD => 'اعتراض على الإحالة المبدئية',
            self::TYPE_DOCUMENTS => 'اعتراض على وثائق المناقصة',
            self::TYPE_EVALUATION => 'اعتراض على التقييم',
            self::TYPE_QUALIFICATION => 'اعتراض على التأهيل',
            self::TYPE_PROCEDURE => 'اعتراض على الإجراءات',
            self::TYPE_OTHER => 'اعتراض آخر',
        ];
    }

    /**
     * حالات الاعتراض
     */
    public static function getStatuses(): array
    {
        return [
            'submitted' => 'مقدم',
            'under_review' => 'قيد الدراسة',
            'accepted' => 'مقبول',
            'partially_accepted' => 'مقبول جزئياً',
            'rejected' => 'مرفوض',
            'escalated' => 'مصعّد للجنة الشكاوى',
        ];
    }

    // ==========================================
    // العلاقات
    // ==========================================

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function objector(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'objector_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==========================================
    // Accessors
    // ==========================================

    /**
     * اسم المعترض
     */
    public function getObjectorDisplayNameAttribute(): string
    {
        return $this->objector?->name ?? $this->objector_name ?? 'غير محدد';
    }

    /**
     * نوع الاعتراض بالعربية
     */
    public function getTypeArabicAttribute(): string
    {
        return self::getObjectionTypes()[$this->objection_type] ?? 'غير محدد';
    }

    /**
     * حالة الاعتراض بالعربية
     */
    public function getStatusArabicAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? 'غير محدد';
    }

    /**
     * لون الحالة
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'submitted' => 'info',
            'under_review' => 'warning',
            'accepted' => 'success',
            'partially_accepted' => 'primary',
            'rejected' => 'danger',
            'escalated' => 'gray',
            default => 'gray',
        };
    }

    /**
     * هل يمكن التصعيد؟
     */
    public function getCanEscalateAttribute(): bool
    {
        return in_array($this->status, ['rejected', 'partially_accepted']) 
            && !$this->is_escalated_to_complaints;
    }

    /**
     * الأيام المتبقية للرد (7 أيام عمل)
     */
    public function getDaysRemainingForResponseAttribute(): ?int
    {
        if ($this->status !== 'submitted' && $this->status !== 'under_review') {
            return null;
        }
        
        $deadline = $this->objection_date->addWeekdays(7);
        return now()->diffInDays($deadline, false);
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['submitted', 'under_review']);
    }

    public function scopeResolved($query)
    {
        return $query->whereIn('status', ['accepted', 'partially_accepted', 'rejected']);
    }

    public function scopeEscalated($query)
    {
        return $query->where('is_escalated_to_complaints', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('objection_type', $type);
    }

    // ==========================================
    // Methods
    // ==========================================

    /**
     * توليد رقم الاعتراض
     */
    public static function generateNumber(Tender $tender): string
    {
        $count = self::where('tender_id', $tender->id)->count() + 1;
        return sprintf('OBJ-%s-%03d', $tender->tender_number, $count);
    }

    /**
     * بدء الدراسة
     */
    public function startReview(): void
    {
        $this->update(['status' => 'under_review']);
    }

    /**
     * قبول الاعتراض
     */
    public function accept(int $userId, string $decision, string $justification = null): void
    {
        $this->update([
            'status' => 'accepted',
            'committee_decision' => $decision,
            'decision_date' => now(),
            'decided_by' => $userId,
            'decision_justification' => $justification,
        ]);
        
        // تحديث العطاء لإيقاف فترة الإحالة
        $this->tender->update(['is_objection_period_active' => true]);
    }

    /**
     * قبول جزئي للاعتراض
     */
    public function partiallyAccept(int $userId, string $decision, string $justification): void
    {
        $this->update([
            'status' => 'partially_accepted',
            'committee_decision' => $decision,
            'decision_date' => now(),
            'decided_by' => $userId,
            'decision_justification' => $justification,
        ]);
    }

    /**
     * رفض الاعتراض
     */
    public function reject(int $userId, string $decision, string $justification): void
    {
        $this->update([
            'status' => 'rejected',
            'committee_decision' => $decision,
            'decision_date' => now(),
            'decided_by' => $userId,
            'decision_justification' => $justification,
        ]);
    }

    /**
     * تصعيد للجنة مراجعة الشكاوى
     */
    public function escalateToComplaintsCommittee(): void
    {
        if (!$this->complaint_fee_paid) {
            throw new \Exception('يجب دفع رسم الشكوى (500 دينار) قبل التصعيد');
        }
        
        $this->update([
            'status' => 'escalated',
            'is_escalated_to_complaints' => true,
            'escalation_date' => now(),
        ]);
        
        // إيقاف إجراءات الشراء
        $this->tender->update(['is_objection_period_active' => true]);
    }

    /**
     * تسجيل قرار لجنة مراجعة الشكاوى
     */
    public function recordComplaintsCommitteeDecision(string $decision, \DateTime $decisionDate): void
    {
        $this->update([
            'complaints_committee_decision' => $decision,
            'complaints_decision_date' => $decisionDate,
        ]);
    }

    /**
     * دفع رسم الشكوى
     */
    public function payComplaintFee(): void
    {
        $this->update(['complaint_fee_paid' => true]);
    }

    /**
     * التحقق من صلاحية تقديم الاعتراض
     */
    public static function canSubmitObjection(Tender $tender, string $type): bool
    {
        // اعتراض على الوثائق يجب تقديمه قبل آخر موعد للتقديم
        if ($type === self::TYPE_DOCUMENTS) {
            return $tender->submission_deadline && now()->lt($tender->submission_deadline);
        }
        
        // اعتراض على الإحالة المبدئية يجب تقديمه خلال فترة الاعتراض
        if ($type === self::TYPE_PRELIMINARY_AWARD) {
            return $tender->is_objection_period_active 
                && $tender->objection_deadline 
                && now()->lte($tender->objection_deadline);
        }
        
        return true;
    }

    // ==========================================
    // Boot
    // ==========================================

    protected static function booted(): void
    {
        static::creating(function (TenderObjection $objection) {
            if (!$objection->objection_number) {
                $objection->objection_number = self::generateNumber($objection->tender);
            }
            if (!$objection->objection_date) {
                $objection->objection_date = now();
            }
            $objection->created_by = auth()->id();
        });
    }
}
