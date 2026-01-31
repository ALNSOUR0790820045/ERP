<?php

namespace App\Traits;

use App\Models\Module;
use App\Models\ModuleStage;
use App\Models\TemporaryPermission;
use App\Services\StagePermissionService;
use Illuminate\Database\Eloquent\Model;

trait HasStagePermissions
{
    /**
     * الحصول على خدمة الصلاحيات
     */
    protected function getPermissionService(): StagePermissionService
    {
        return app(StagePermissionService::class);
    }

    /**
     * التحقق من صلاحية المستخدم على مرحلة معينة
     */
    public function canPerformOnStage(
        string $moduleCode,
        string $stageCode,
        string $permissionCode,
        ?Model $record = null
    ): bool {
        $userId = auth()->id();
        
        if (!$userId) {
            return false;
        }

        // التحقق من المشرف العام
        if (auth()->user()->hasRole('super_admin')) {
            return true;
        }

        // التحقق من الصلاحية العادية
        $hasPermission = $this->getPermissionService()->can(
            $userId,
            $moduleCode,
            $stageCode,
            $permissionCode
        );

        if ($hasPermission) {
            return true;
        }

        // التحقق من الصلاحية المؤقتة للسجل المحدد
        if ($record) {
            $module = Module::where('code', $moduleCode)->first();
            $stage = ModuleStage::where('module_id', $module?->id)
                ->where('code', $stageCode)
                ->first();

            if ($module && $stage) {
                return TemporaryPermission::hasValidPermission(
                    $userId,
                    $stage->id,
                    $permissionCode,
                    get_class($record),
                    $record->id
                );
            }
        }

        return false;
    }

    /**
     * الحصول على المراحل المرئية للمستخدم
     */
    public function getVisibleStagesForUser(string $moduleCode): array
    {
        $userId = auth()->id();
        
        if (!$userId) {
            return [];
        }

        // المشرف العام يرى كل شيء
        if (auth()->user()->hasRole('super_admin')) {
            $module = Module::where('code', $moduleCode)->first();
            return $module ? $module->stages->toArray() : [];
        }

        return $this->getPermissionService()->getVisibleStages($userId, $moduleCode);
    }

    /**
     * تصفية البيانات حسب المراحل المرئية
     */
    public function filterByVisibleStages($query, string $moduleCode, string $stageColumn = 'stage')
    {
        $visibleStages = $this->getVisibleStagesForUser($moduleCode);
        $stageCodes = array_map(fn($stage) => $stage->code ?? $stage['code'], $visibleStages);

        if (empty($stageCodes)) {
            // لا توجد مراحل مرئية - لا تعرض أي شيء
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($stageColumn, $stageCodes);
    }

    /**
     * التحقق من إمكانية عرض سجل معين
     */
    public function canViewRecord(Model $record, string $moduleCode, string $stageColumn = 'stage'): bool
    {
        $stageCode = $record->{$stageColumn};
        return $this->canPerformOnStage($moduleCode, $stageCode, 'view', $record);
    }

    /**
     * التحقق من إمكانية تعديل سجل معين
     */
    public function canEditRecord(Model $record, string $moduleCode, string $stageColumn = 'stage'): bool
    {
        $stageCode = $record->{$stageColumn};
        return $this->canPerformOnStage($moduleCode, $stageCode, 'update', $record);
    }

    /**
     * التحقق من إمكانية حذف سجل معين
     */
    public function canDeleteRecord(Model $record, string $moduleCode, string $stageColumn = 'stage'): bool
    {
        $stageCode = $record->{$stageColumn};
        return $this->canPerformOnStage($moduleCode, $stageCode, 'delete', $record);
    }

    /**
     * التحقق من إمكانية الموافقة على سجل معين
     */
    public function canApproveRecord(Model $record, string $moduleCode, string $stageColumn = 'stage'): bool
    {
        $stageCode = $record->{$stageColumn};
        return $this->canPerformOnStage($moduleCode, $stageCode, 'approve', $record);
    }

    /**
     * التحقق من إمكانية رفض سجل معين
     */
    public function canRejectRecord(Model $record, string $moduleCode, string $stageColumn = 'stage'): bool
    {
        $stageCode = $record->{$stageColumn};
        return $this->canPerformOnStage($moduleCode, $stageCode, 'reject', $record);
    }

    /**
     * التحقق من إمكانية تصعيد سجل معين
     */
    public function canEscalateRecord(Model $record, string $moduleCode, string $stageColumn = 'stage'): bool
    {
        $stageCode = $record->{$stageColumn};
        return $this->canPerformOnStage($moduleCode, $stageCode, 'escalate', $record);
    }

    /**
     * التحقق من إمكانية إرجاع سجل معين
     */
    public function canReturnRecord(Model $record, string $moduleCode, string $stageColumn = 'stage'): bool
    {
        $stageCode = $record->{$stageColumn};
        return $this->canPerformOnStage($moduleCode, $stageCode, 'return', $record);
    }

    /**
     * منح صلاحية مؤقتة للتعديل
     */
    public function grantTemporaryEditPermission(
        int $userId,
        Model $record,
        string $moduleCode,
        string $stageCode,
        string $reason,
        int $hoursValid = 48
    ): TemporaryPermission {
        return $this->getPermissionService()->grantReturnPermission(
            $userId,
            $moduleCode,
            $stageCode,
            'update',
            get_class($record),
            $record->id,
            $reason,
            $hoursValid
        );
    }

    /**
     * الحصول على الصلاحيات المتاحة للمستخدم الحالي على وحدة معينة
     */
    public function getCurrentUserModulePermissions(string $moduleCode): array
    {
        $userId = auth()->id();
        
        if (!$userId) {
            return [];
        }

        return $this->getPermissionService()->getUserModulePermissions($userId, $moduleCode);
    }
}
