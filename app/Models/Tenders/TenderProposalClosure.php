<?php

namespace App\Models\Tenders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * إغلاق العرض قبل التقديم
 * Tender Proposal Closure
 */
class TenderProposalClosure extends Model
{
    protected $fillable = [
        'tender_id',
        'closure_datetime',
        // الفني
        'technical_complete',
        'technical_checklist',
        'technical_missing_items',
        // المالي
        'financial_complete',
        'financial_checklist',
        'financial_missing_items',
        // الكفالات
        'bonds_ready',
        'bonds_notes',
        // الإدارية
        'admin_docs_complete',
        'admin_docs_checklist',
        // المراجعة
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        // الموافقة
        'approved_by',
        'approved_at',
        // السعر النهائي
        'final_price',
        'price_justification',
        'status',
    ];

    protected $casts = [
        'closure_datetime' => 'datetime',
        'technical_complete' => 'boolean',
        'technical_checklist' => 'array',
        'financial_complete' => 'boolean',
        'financial_checklist' => 'array',
        'bonds_ready' => 'boolean',
        'admin_docs_complete' => 'boolean',
        'admin_docs_checklist' => 'array',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'final_price' => 'decimal:3',
    ];

    // الحالات
    public const STATUSES = [
        'draft' => 'مسودة',
        'reviewing' => 'قيد المراجعة',
        'approved' => 'موافق للتقديم',
        'rejected' => 'مرفوض',
    ];

    // قوائم الفحص الافتراضية
    public const DEFAULT_TECHNICAL_CHECKLIST = [
        'company_profile' => 'ملف تعريف الشركة',
        'organization_chart' => 'الهيكل التنظيمي',
        'similar_projects' => 'مشاريع مماثلة',
        'key_personnel' => 'الكوادر الرئيسية',
        'equipment_list' => 'قائمة المعدات',
        'method_statement' => 'منهجية التنفيذ',
        'work_program' => 'البرنامج الزمني',
        'quality_plan' => 'خطة الجودة',
        'safety_plan' => 'خطة السلامة',
        'classification_certificate' => 'شهادة التصنيف',
        'registration_certificate' => 'شهادة التسجيل',
        'tax_clearance' => 'براءة ذمة ضريبية',
        'social_security_clearance' => 'براءة ذمة ضمان',
    ];

    public const DEFAULT_FINANCIAL_CHECKLIST = [
        'priced_boq' => 'جدول الكميات المسعر',
        'price_summary' => 'ملخص الأسعار',
        'bid_letter' => 'خطاب العرض',
        'bid_bond' => 'كفالة الدخول',
        'financial_statements' => 'القوائم المالية',
        'bank_reference' => 'خطاب البنك',
    ];

    public const DEFAULT_ADMIN_CHECKLIST = [
        'bid_form' => 'نموذج العطاء',
        'power_of_attorney' => 'التفويض',
        'envelope_sealing' => 'ختم المظاريف',
        'copies_prepared' => 'النسخ جاهزة',
        'labels_attached' => 'الملصقات مرفقة',
    ];

    // العلاقات
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Methods
    public function isComplete(): bool
    {
        return $this->technical_complete 
            && $this->financial_complete 
            && $this->bonds_ready 
            && $this->admin_docs_complete;
    }

    public function review(User $user, string $notes = null): void
    {
        $this->update([
            'status' => 'reviewing',
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    public function approve(User $user): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $this->tender->update(['proposal_closed' => true]);
    }

    public function reject(User $user, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'review_notes' => $reason,
        ]);
    }

    public function getCompletionPercentageAttribute(): float
    {
        $items = 0;
        $completed = 0;

        if ($this->technical_complete) $completed++;
        $items++;

        if ($this->financial_complete) $completed++;
        $items++;

        if ($this->bonds_ready) $completed++;
        $items++;

        if ($this->admin_docs_complete) $completed++;
        $items++;

        return $items > 0 ? ($completed / $items) * 100 : 0;
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getIsReadyForSubmissionAttribute(): bool
    {
        return $this->status === 'approved' && $this->isComplete();
    }
}
