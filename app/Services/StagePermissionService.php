<?php

namespace App\Services;

use App\Models\Module;
use App\Models\ModuleStage;
use App\Models\PermissionType;
use App\Models\RoleStagePermission;
use App\Models\TemporaryPermission;
use App\Models\User;
use App\Models\UserStagePermission;
use Illuminate\Support\Facades\Cache;

class StagePermissionService
{
    /**
     * مدة التخزين المؤقت (بالدقائق)
     */
    protected const CACHE_TTL = 5;

    /**
     * التحقق من صلاحية مستخدم على مرحلة معينة
     */
    public function can(
        int $userId,
        string $moduleCode,
        string $stageCode,
        string $permissionCode,
        ?string $permissionableType = null,
        ?int $permissionableId = null
    ): bool {
        $user = User::find($userId);

        // المشرف العام له جميع الصلاحيات
        if ($user && $user->hasRole('super_admin')) {
            return true;
        }

        // البحث عن الوحدة والمرحلة
        $module = $this->getModuleByCode($moduleCode);
        $stage = $this->getStageByCode($module?->id, $stageCode);

        if (!$module || !$stage) {
            return false;
        }

        // التحقق من الصلاحية المباشرة للمستخدم
        if ($this->hasDirectPermission($userId, $stage->id, $permissionCode)) {
            return true;
        }

        // التحقق من الصلاحية من خلال الأدوار
        if ($this->hasRolePermission($userId, $stage->id, $permissionCode)) {
            return true;
        }

        // التحقق من الصلاحية المؤقتة
        if ($permissionableType && $permissionableId) {
            if ($this->hasTemporaryPermission($userId, $stage->id, $permissionCode, $permissionableType, $permissionableId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * التحقق من إمكانية رؤية مرحلة معينة
     */
    public function canViewStage(int $userId, string $moduleCode, string $stageCode): bool
    {
        $user = User::find($userId);

        // المشرف العام يرى كل شيء
        if ($user && $user->hasRole('super_admin')) {
            return true;
        }

        $module = $this->getModuleByCode($moduleCode);
        $stage = $this->getStageByCode($module?->id, $stageCode);

        if (!$module || !$stage) {
            return false;
        }

        // التحقق من صلاحية المستخدم المباشرة
        $userPermission = UserStagePermission::where('user_id', $userId)
            ->where('stage_id', $stage->id)
            ->where('can_view_stage', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($userPermission) {
            return true;
        }

        // التحقق من صلاحية الأدوار
        $roleIds = $user->roles->pluck('id');
        $rolePermission = RoleStagePermission::whereIn('role_id', $roleIds)
            ->where('stage_id', $stage->id)
            ->where('can_view_stage', true)
            ->first();

        return (bool) $rolePermission;
    }

    /**
     * الحصول على المراحل المرئية للمستخدم
     */
    public function getVisibleStages(int $userId, string $moduleCode): array
    {
        $module = $this->getModuleByCode($moduleCode);
        if (!$module) {
            return [];
        }

        $stages = $module->stages;
        $visibleStages = [];

        foreach ($stages as $stage) {
            if ($this->canViewStage($userId, $moduleCode, $stage->code)) {
                $visibleStages[] = $stage;
            }
        }

        return $visibleStages;
    }

    /**
     * الحصول على صلاحيات المستخدم على وحدة معينة
     */
    public function getUserModulePermissions(int $userId, string $moduleCode): array
    {
        $module = $this->getModuleByCode($moduleCode);
        if (!$module) {
            return [];
        }

        $result = [];
        $stages = $module->stages;

        foreach ($stages as $stage) {
            $stagePermissions = $this->getUserStagePermissions($userId, $stage->id);
            if (!empty($stagePermissions)) {
                $result[$stage->code] = [
                    'stage' => $stage,
                    'permissions' => $stagePermissions,
                    'can_view' => $this->canViewStage($userId, $moduleCode, $stage->code),
                ];
            }
        }

        return $result;
    }

    /**
     * الحصول على صلاحيات المستخدم على مرحلة معينة
     */
    public function getUserStagePermissions(int $userId, int $stageId): array
    {
        $permissions = [];

        // صلاحيات مباشرة
        $directPermissions = UserStagePermission::where('user_id', $userId)
            ->where('stage_id', $stageId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with('permissionType')
            ->get();

        foreach ($directPermissions as $perm) {
            $permissions[$perm->permissionType->code] = [
                'source' => 'direct',
                'type' => $perm->permissionType,
            ];
        }

        // صلاحيات من الأدوار
        $user = User::find($userId);
        if ($user) {
            $roleIds = $user->roles->pluck('id');
            $rolePermissions = RoleStagePermission::whereIn('role_id', $roleIds)
                ->where('stage_id', $stageId)
                ->with('permissionType')
                ->get();

            foreach ($rolePermissions as $perm) {
                if (!isset($permissions[$perm->permissionType->code])) {
                    $permissions[$perm->permissionType->code] = [
                        'source' => 'role',
                        'type' => $perm->permissionType,
                    ];
                }
            }
        }

        return $permissions;
    }

    /**
     * منح صلاحية مؤقتة عند إرجاع العطاء
     */
    public function grantReturnPermission(
        int $userId,
        string $moduleCode,
        string $stageCode,
        string $permissionCode,
        string $permissionableType,
        int $permissionableId,
        string $reason,
        int $hoursValid = 48
    ): TemporaryPermission {
        $module = $this->getModuleByCode($moduleCode);
        $stage = $this->getStageByCode($module->id, $stageCode);
        $permissionType = PermissionType::where('code', $permissionCode)->first();

        return TemporaryPermission::grant(
            $userId,
            $module->id,
            $stage->id,
            $permissionType->id,
            $reason,
            now()->addHours($hoursValid),
            $permissionableType,
            $permissionableId
        );
    }

    /**
     * إلغاء الصلاحية المؤقتة
     */
    public function revokeTemporaryPermission(int $permissionId, ?int $revokedBy = null): bool
    {
        $permission = TemporaryPermission::find($permissionId);
        if ($permission) {
            $permission->revoke($revokedBy);
            return true;
        }
        return false;
    }

    /**
     * التحقق من صلاحية مباشرة
     */
    protected function hasDirectPermission(int $userId, int $stageId, string $permissionCode): bool
    {
        return UserStagePermission::hasPermission($userId, $stageId, $permissionCode);
    }

    /**
     * التحقق من صلاحية من خلال الأدوار
     */
    protected function hasRolePermission(int $userId, int $stageId, string $permissionCode): bool
    {
        return RoleStagePermission::userHasPermissionThroughRoles($userId, $stageId, $permissionCode);
    }

    /**
     * التحقق من صلاحية مؤقتة
     */
    protected function hasTemporaryPermission(
        int $userId,
        int $stageId,
        string $permissionCode,
        string $permissionableType,
        int $permissionableId
    ): bool {
        return TemporaryPermission::hasValidPermission(
            $userId,
            $stageId,
            $permissionCode,
            $permissionableType,
            $permissionableId
        );
    }

    /**
     * الحصول على الوحدة بالكود (مع تخزين مؤقت)
     */
    protected function getModuleByCode(string $code): ?Module
    {
        return Cache::remember("module_{$code}", self::CACHE_TTL * 60, function () use ($code) {
            return Module::where('code', $code)->first();
        });
    }

    /**
     * الحصول على المرحلة بالكود (مع تخزين مؤقت)
     */
    protected function getStageByCode(?int $moduleId, string $code): ?ModuleStage
    {
        if (!$moduleId) {
            return null;
        }

        return Cache::remember("stage_{$moduleId}_{$code}", self::CACHE_TTL * 60, function () use ($moduleId, $code) {
            return ModuleStage::where('module_id', $moduleId)
                ->where('code', $code)
                ->first();
        });
    }

    /**
     * مسح التخزين المؤقت
     */
    public function clearCache(): void
    {
        // مسح التخزين المؤقت للوحدات والمراحل
        $modules = Module::all();
        foreach ($modules as $module) {
            Cache::forget("module_{$module->code}");
            $stages = $module->stages;
            foreach ($stages as $stage) {
                Cache::forget("stage_{$module->id}_{$stage->code}");
            }
        }
    }

    /**
     * الحصول على ملخص صلاحيات المستخدم
     */
    public function getUserPermissionsSummary(int $userId): array
    {
        $summary = [];
        $modules = Module::where('is_active', true)->get();

        foreach ($modules as $module) {
            $modulePermissions = $this->getUserModulePermissions($userId, $module->code);
            if (!empty($modulePermissions)) {
                $summary[$module->code] = [
                    'module' => $module,
                    'stages' => $modulePermissions,
                ];
            }
        }

        return $summary;
    }
}
