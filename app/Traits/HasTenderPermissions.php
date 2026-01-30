<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

trait HasTenderPermissions
{
    /**
     * التحقق من صلاحية المستخدم الحالي
     */
    protected function userCan(string $permissionCode): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // إذا كان المستخدم مدير نظام، لديه كل الصلاحيات
        if ($user->role?->code === 'super_admin') {
            return true;
        }

        // التحقق من الصلاحية عبر الدور
        if ($user->role) {
            return $user->role->hasPermission($permissionCode);
        }

        return false;
    }

    /**
     * التحقق من صلاحيات متعددة (أي واحدة منها)
     */
    protected function userCanAny(array $permissionCodes): bool
    {
        foreach ($permissionCodes as $code) {
            if ($this->userCan($code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * التحقق من صلاحيات متعددة (جميعها)
     */
    protected function userCanAll(array $permissionCodes): bool
    {
        foreach ($permissionCodes as $code) {
            if (!$this->userCan($code)) {
                return false;
            }
        }
        return true;
    }

    /**
     * صلاحيات العطاءات
     */
    protected function canViewTenders(): bool
    {
        return $this->userCan('tenders.tender.view');
    }

    protected function canCreateTender(): bool
    {
        return $this->userCan('tenders.tender.create');
    }

    protected function canUpdateTender(): bool
    {
        return $this->userCan('tenders.tender.update');
    }

    protected function canDeleteTender(): bool
    {
        return $this->userCan('tenders.tender.delete');
    }

    protected function canSendForStudy(): bool
    {
        return $this->userCan('tenders.tender.send_for_study');
    }

    protected function canViewStudy(): bool
    {
        return $this->userCan('tenders.study.view');
    }

    protected function canEditStudy(): bool
    {
        return $this->userCan('tenders.study.edit');
    }

    protected function canMakeGoDecision(): bool
    {
        return $this->userCan('tenders.decision.go');
    }

    protected function canMakeNoGoDecision(): bool
    {
        return $this->userCan('tenders.decision.no_go');
    }

    protected function canViewPricing(): bool
    {
        return $this->userCan('tenders.pricing.view');
    }

    protected function canEditPricing(): bool
    {
        return $this->userCan('tenders.pricing.edit');
    }

    protected function canApprovePricing(): bool
    {
        return $this->userCan('tenders.pricing.approve');
    }

    protected function canViewPreparation(): bool
    {
        return $this->userCan('tenders.preparation.view');
    }

    protected function canEditPreparation(): bool
    {
        return $this->userCan('tenders.preparation.edit');
    }

    protected function canSubmitTender(): bool
    {
        return $this->userCan('tenders.submission.submit');
    }

    protected function canViewOpening(): bool
    {
        return $this->userCan('tenders.opening.view');
    }

    protected function canEditOpening(): bool
    {
        return $this->userCan('tenders.opening.edit');
    }

    protected function canSetResult(): bool
    {
        return $this->userCan('tenders.result.set');
    }

    protected function canExportTenders(): bool
    {
        return $this->userCan('tenders.tender.export');
    }

    protected function canViewReports(): bool
    {
        return $this->userCan('tenders.reports.view');
    }

    /**
     * الحصول على المراحل المتاحة للمستخدم
     */
    protected function getAvailableSteps(): array
    {
        $steps = [];

        // المرحلة 1: الرصد والتسجيل
        if ($this->canCreateTender() || $this->canUpdateTender()) {
            $steps[] = 'الرصد والتسجيل';
        }

        // المرحلة 2: الدراسة والقرار
        if ($this->canViewStudy() || $this->canEditStudy() || $this->canMakeGoDecision()) {
            $steps[] = 'الدراسة والقرار';
        }

        // المرحلة 3: التسعير
        if ($this->canViewPricing() || $this->canEditPricing()) {
            $steps[] = 'التسعير';
        }

        // المرحلة 4: التجهيز والتقديم
        if ($this->canViewPreparation() || $this->canEditPreparation() || $this->canSubmitTender()) {
            $steps[] = 'التجهيز والتقديم';
        }

        // المرحلة 5: الفتح والنتيجة
        if ($this->canViewOpening() || $this->canEditOpening() || $this->canSetResult()) {
            $steps[] = 'الفتح والنتيجة';
        }

        return $steps;
    }

    /**
     * التحقق من إمكانية الوصول لمرحلة معينة
     */
    protected function canAccessStep(string $stepName): bool
    {
        return in_array($stepName, $this->getAvailableSteps());
    }
}
