<?php

namespace App\Policies;

use App\Models\Tender;
use App\Models\User;
use App\Services\StagePermissionService;
use App\Models\Module;
use App\Models\ModuleStage;
use App\Models\TemporaryPermission;

class TenderPolicy
{
    protected StagePermissionService $permissionService;

    public function __construct(StagePermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * تحديد ما إذا كان المستخدم مشرف عام
     */
    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * الحصول على مرحلة العطاء
     */
    protected function getTenderStageCode(Tender $tender): string
    {
        // افتراضياً نستخدم حقل stage أو status
        return $tender->stage ?? $tender->status ?? 'monitoring';
    }

    /**
     * التحقق من الصلاحية على المرحلة
     */
    protected function checkStagePermission(User $user, string $stageCode, string $permissionCode): bool
    {
        return $this->permissionService->can(
            $user->id,
            'tenders',
            $stageCode,
            $permissionCode
        );
    }

    /**
     * التحقق من الصلاحية المؤقتة
     */
    protected function hasTemporaryPermission(User $user, Tender $tender, string $permissionCode): bool
    {
        $module = Module::where('code', 'tenders')->first();
        $stage = ModuleStage::where('module_id', $module?->id)
            ->where('code', $this->getTenderStageCode($tender))
            ->first();

        if (!$module || !$stage) {
            return false;
        }

        return TemporaryPermission::hasValidPermission(
            $user->id,
            $stage->id,
            $permissionCode,
            Tender::class,
            $tender->id
        );
    }

    /**
     * صلاحية العرض
     */
    public function viewAny(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // يمكنه رؤية القائمة إذا كان لديه صلاحية view على أي مرحلة
        $module = Module::where('code', 'tenders')->first();
        if (!$module) {
            return false;
        }

        $stages = $module->stages;
        foreach ($stages as $stage) {
            if ($this->checkStagePermission($user, $stage->code, 'view')) {
                return true;
            }
        }

        return false;
    }

    /**
     * صلاحية عرض عطاء محدد
     */
    public function view(User $user, Tender $tender): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $stageCode = $this->getTenderStageCode($tender);
        
        // التحقق من صلاحية المرحلة
        if ($this->checkStagePermission($user, $stageCode, 'view')) {
            return true;
        }

        // التحقق من الصلاحية المؤقتة
        return $this->hasTemporaryPermission($user, $tender, 'view');
    }

    /**
     * صلاحية الإنشاء
     */
    public function create(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // الإنشاء يتم في مرحلة الرصد فقط
        return $this->checkStagePermission($user, 'monitoring', 'create');
    }

    /**
     * صلاحية التعديل
     */
    public function update(User $user, Tender $tender): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $stageCode = $this->getTenderStageCode($tender);

        // التحقق من صلاحية المرحلة
        if ($this->checkStagePermission($user, $stageCode, 'update')) {
            return true;
        }

        // التحقق من الصلاحية المؤقتة (عند إرجاع العطاء)
        return $this->hasTemporaryPermission($user, $tender, 'update');
    }

    /**
     * صلاحية الحذف
     */
    public function delete(User $user, Tender $tender): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $stageCode = $this->getTenderStageCode($tender);
        
        // الحذف عادة في مرحلة الرصد فقط
        return $this->checkStagePermission($user, $stageCode, 'delete');
    }

    /**
     * صلاحية الحذف المجمع
     */
    public function deleteAny(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->checkStagePermission($user, 'monitoring', 'delete');
    }

    /**
     * صلاحية الموافقة
     */
    public function approve(User $user, Tender $tender): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $stageCode = $this->getTenderStageCode($tender);
        return $this->checkStagePermission($user, $stageCode, 'approve');
    }

    /**
     * صلاحية الرفض
     */
    public function reject(User $user, Tender $tender): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $stageCode = $this->getTenderStageCode($tender);
        return $this->checkStagePermission($user, $stageCode, 'reject');
    }

    /**
     * صلاحية التصعيد
     */
    public function escalate(User $user, Tender $tender): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $stageCode = $this->getTenderStageCode($tender);
        return $this->checkStagePermission($user, $stageCode, 'escalate');
    }

    /**
     * صلاحية الإرجاع
     */
    public function return(User $user, Tender $tender): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $stageCode = $this->getTenderStageCode($tender);
        return $this->checkStagePermission($user, $stageCode, 'return');
    }

    /**
     * صلاحية التفويض
     */
    public function delegate(User $user, Tender $tender): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $stageCode = $this->getTenderStageCode($tender);
        return $this->checkStagePermission($user, $stageCode, 'delegate');
    }

    /**
     * صلاحية نقل العطاء للمرحلة التالية
     */
    public function moveToNextStage(User $user, Tender $tender): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $stageCode = $this->getTenderStageCode($tender);
        
        // يحتاج صلاحية الموافقة للنقل للمرحلة التالية
        return $this->checkStagePermission($user, $stageCode, 'approve');
    }

    /**
     * التحقق من إمكانية رؤية المرحلة
     */
    public function viewStage(User $user, string $stageCode): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->permissionService->canViewStage($user->id, 'tenders', $stageCode);
    }
}
