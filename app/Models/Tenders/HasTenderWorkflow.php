<?php

namespace App\Models\Tenders;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * علاقات سير عمل العطاء المحسّن
 * Enhanced Tender Workflow Relations
 * 
 * يتضمن الخطوات الـ 17 لوثيقة العطاءات الحكومية الموحدة:
 * 1. رصد المناقصات
 * 2. موافقة الشراء
 * 3. شراء الوثائق
 * 4. إدخال الوثائق
 * 5. زيارة الموقع
 * 6. تقييم المشروع
 * 7. قرار المشاركة
 * 8. التسعير
 * 9. إدخال الملاحق
 * 10. تجهيز العرض الفني
 * 11. تجهيز العرض المالي
 * 12. تجهيز الكفالات
 * 13. إغلاق العرض
 * 14. تقديم العرض
 * 15. فتح العروض
 * 16. إدخال النتائج
 * 17. متابعة الإحالة والتحويل لمشروع
 */
trait HasTenderWorkflow
{
    /**
     * مصدر رصد العطاء
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(TenderSource::class, 'source_id');
    }

    /**
     * سجلات الاكتشاف
     */
    public function discoveries(): HasMany
    {
        return $this->hasMany(TenderDiscovery::class);
    }

    /**
     * موافقات الشراء
     */
    public function purchaseApprovals(): HasMany
    {
        return $this->hasMany(TenderPurchaseApproval::class);
    }

    /**
     * آخر موافقة شراء
     */
    public function latestPurchaseApproval(): HasOne
    {
        return $this->hasOne(TenderPurchaseApproval::class)->latestOfMany();
    }

    /**
     * زيارات الموقع
     */
    public function siteVisits(): HasMany
    {
        return $this->hasMany(TenderSiteVisit::class);
    }

    /**
     * آخر زيارة موقع
     */
    public function latestSiteVisit(): HasOne
    {
        return $this->hasOne(TenderSiteVisit::class)->latestOfMany();
    }

    /**
     * تقييمات العطاء
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(TenderEvaluation::class);
    }

    /**
     * آخر تقييم
     */
    public function latestEvaluation(): HasOne
    {
        return $this->hasOne(TenderEvaluation::class)->latestOfMany();
    }

    /**
     * تجديدات الكفالات
     */
    public function bondRenewals(): HasMany
    {
        return $this->hasManyThrough(
            TenderBondRenewal::class,
            \App\Models\TenderBond::class,
            'tender_id',
            'bond_id'
        );
    }

    /**
     * سحب الكفالات
     */
    public function bondWithdrawals(): HasMany
    {
        return $this->hasManyThrough(
            TenderBondWithdrawal::class,
            \App\Models\TenderBond::class,
            'tender_id',
            'bond_id'
        );
    }

    /**
     * إغلاق العرض
     */
    public function proposalClosure(): HasOne
    {
        return $this->hasOne(TenderProposalClosure::class);
    }

    /**
     * تتبع الإحالة
     */
    public function awardTracking(): HasMany
    {
        return $this->hasMany(TenderAwardTracking::class);
    }

    /**
     * آخر تتبع إحالة
     */
    public function latestAwardTracking(): HasOne
    {
        return $this->hasOne(TenderAwardTracking::class)->latestOfMany();
    }

    /**
     * تحويل لمشروع
     */
    public function projectConversion(): HasOne
    {
        return $this->hasOne(TenderToProjectConversion::class);
    }

    /**
     * سجل المراحل
     */
    public function stageLogs(): HasMany
    {
        return $this->hasMany(TenderStageLog::class)->orderBy('stage_order');
    }

    /**
     * تنبيهات العطاء
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(TenderAlert::class);
    }

    /**
     * التنبيهات النشطة
     */
    public function activeAlerts(): HasMany
    {
        return $this->hasMany(TenderAlert::class)
            ->whereIn('status', ['pending', 'sent']);
    }

    // ================ WORKFLOW METHODS ================

    /**
     * تهيئة مراحل العطاء
     */
    public function initializeWorkflow(): void
    {
        TenderStageLog::initializeForTender($this);
    }

    /**
     * الحصول على المرحلة الحالية
     */
    public function getCurrentStageLog(): ?TenderStageLog
    {
        return $this->stageLogs()
            ->where('status', 'in_progress')
            ->first() ?? $this->stageLogs()
            ->where('status', 'not_started')
            ->orderBy('stage_order')
            ->first();
    }

    /**
     * بدء مرحلة معينة
     */
    public function startStage(string $stage): TenderStageLog
    {
        $stageLog = $this->stageLogs()->where('stage', $stage)->first();
        
        if (!$stageLog) {
            throw new \Exception("المرحلة غير موجودة: {$stage}");
        }

        $stageLog->start();
        $this->update(['current_stage' => $stage]);
        
        return $stageLog;
    }

    /**
     * إكمال المرحلة الحالية
     */
    public function completeCurrentStage(?string $notes = null): ?TenderStageLog
    {
        $currentStage = $this->getCurrentStageLog();
        
        if ($currentStage && $currentStage->status === 'in_progress') {
            $currentStage->complete(auth()->user(), $notes);
            return $currentStage;
        }
        
        return null;
    }

    /**
     * الانتقال للمرحلة التالية
     */
    public function moveToNextStage(): ?TenderStageLog
    {
        $nextStage = $this->stageLogs()
            ->where('status', 'not_started')
            ->orderBy('stage_order')
            ->first();

        if ($nextStage) {
            $nextStage->start();
            return $nextStage;
        }

        return null;
    }

    /**
     * الحصول على نسبة إكمال سير العمل
     */
    public function getWorkflowProgress(): array
    {
        $total = $this->stageLogs()->count();
        $completed = $this->stageLogs()->whereIn('status', ['completed', 'skipped'])->count();
        $inProgress = $this->stageLogs()->where('status', 'in_progress')->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'pending' => $total - $completed - $inProgress,
            'percentage' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
        ];
    }

    /**
     * هل تمت الموافقة على الشراء؟
     */
    public function isPurchaseApproved(): bool
    {
        return $this->purchase_approved || 
            $this->purchaseApprovals()->where('status', 'approved')->exists();
    }

    /**
     * هل تمت زيارة الموقع؟
     */
    public function hasSiteVisit(): bool
    {
        return $this->site_visited || $this->siteVisits()->exists();
    }

    /**
     * هل العرض جاهز للتقديم؟
     */
    public function isReadyForSubmission(): bool
    {
        return $this->proposal_closed || 
            ($this->proposalClosure && $this->proposalClosure->is_ready_for_submission);
    }

    /**
     * هل الكفالة تحتاج تجديد؟
     */
    public function hasBondsNeedingRenewal(int $daysThreshold = 7): bool
    {
        return $this->bonds()
            ->where('status', 'active')
            ->where('expiry_date', '<=', now()->addDays($daysThreshold))
            ->exists();
    }

    /**
     * سحب كفالة الدخول (بعد الفوز أو الخسارة)
     */
    public function withdrawBidBond(string $reason = 'tender_won'): ?TenderBondWithdrawal
    {
        $bidBond = $this->bonds()
            ->where(function($q) {
                $q->where('bond_type', 'bid')
                  ->orWhere('is_bid_bond', true);
            })
            ->where('is_withdrawn', false)
            ->first();

        if (!$bidBond) {
            return null;
        }

        return TenderBondWithdrawal::create([
            'bond_id' => $bidBond->id,
            'withdrawal_reason' => $reason,
            'request_date' => now(),
            'status' => 'pending',
            'requested_by' => auth()->id(),
        ]);
    }

    /**
     * تحويل العطاء إلى مشروع
     */
    public function convertToProject(array $data = []): TenderToProjectConversion
    {
        if ($this->result !== 'won') {
            throw new \Exception('لا يمكن تحويل عطاء غير فائز إلى مشروع');
        }

        return TenderToProjectConversion::create(array_merge([
            'tender_id' => $this->id,
            'conversion_date' => now(),
            'converted_by' => auth()->id(),
            'project_name_ar' => $this->name_ar,
            'project_name_en' => $this->name_en,
            'contract_value' => $this->winning_price ?? $this->submitted_price,
            'status' => 'pending',
        ], $data));
    }

    /**
     * إنشاء تنبيه للعطاء
     */
    public function createAlert(string $type, string $titleAr, ?string $dueDate = null, string $priority = 'medium'): TenderAlert
    {
        return TenderAlert::create([
            'tender_id' => $this->id,
            'alert_type' => $type,
            'title_ar' => $titleAr,
            'alert_date' => now(),
            'due_date' => $dueDate,
            'priority' => $priority,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);
    }
}
