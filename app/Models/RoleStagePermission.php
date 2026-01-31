<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleStagePermission extends Model
{
    protected $fillable = [
        'role_id',
        'module_id',
        'stage_id',
        'permission_type_id',
        'can_view_stage',
    ];

    protected $casts = [
        'can_view_stage' => 'boolean',
    ];

    /**
     * الدور صاحب الصلاحية
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * الوحدة
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * المرحلة
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(ModuleStage::class, 'stage_id');
    }

    /**
     * نوع الصلاحية
     */
    public function permissionType(): BelongsTo
    {
        return $this->belongsTo(PermissionType::class);
    }

    /**
     * الحصول على صلاحيات دور معين على وحدة معينة
     */
    public static function getRoleModulePermissions(int $roleId, int $moduleId)
    {
        return self::where('role_id', $roleId)
            ->where('module_id', $moduleId)
            ->with(['stage', 'permissionType'])
            ->get();
    }

    /**
     * الحصول على صلاحيات دور على مرحلة معينة
     */
    public static function getRoleStagePermissions(int $roleId, int $stageId)
    {
        return self::where('role_id', $roleId)
            ->where('stage_id', $stageId)
            ->with('permissionType')
            ->get();
    }

    /**
     * التحقق من وجود صلاحية معينة للدور
     */
    public static function roleHasPermission(int $roleId, int $stageId, string $permissionCode): bool
    {
        return self::where('role_id', $roleId)
            ->where('stage_id', $stageId)
            ->whereHas('permissionType', function ($query) use ($permissionCode) {
                $query->where('code', $permissionCode);
            })
            ->exists();
    }

    /**
     * منح صلاحية لدور
     */
    public static function grantPermission(
        int $roleId,
        int $moduleId,
        int $stageId,
        int $permissionTypeId,
        bool $canViewStage = true
    ): self {
        return self::updateOrCreate(
            [
                'role_id' => $roleId,
                'module_id' => $moduleId,
                'stage_id' => $stageId,
                'permission_type_id' => $permissionTypeId,
            ],
            [
                'can_view_stage' => $canViewStage,
            ]
        );
    }

    /**
     * سحب صلاحية من دور
     */
    public static function revokePermission(int $roleId, int $stageId, int $permissionTypeId): bool
    {
        return self::where('role_id', $roleId)
            ->where('stage_id', $stageId)
            ->where('permission_type_id', $permissionTypeId)
            ->delete() > 0;
    }

    /**
     * الحصول على جميع صلاحيات مستخدم من خلال أدواره
     */
    public static function getUserPermissionsThroughRoles(int $userId, int $moduleId)
    {
        $user = User::find($userId);
        if (!$user) {
            return collect();
        }

        $roleIds = $user->roles->pluck('id');

        return self::whereIn('role_id', $roleIds)
            ->where('module_id', $moduleId)
            ->with(['stage', 'permissionType'])
            ->get();
    }

    /**
     * التحقق من صلاحية مستخدم من خلال أدواره
     */
    public static function userHasPermissionThroughRoles(int $userId, int $stageId, string $permissionCode): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        $roleIds = $user->roles->pluck('id');

        return self::whereIn('role_id', $roleIds)
            ->where('stage_id', $stageId)
            ->whereHas('permissionType', function ($query) use ($permissionCode) {
                $query->where('code', $permissionCode);
            })
            ->exists();
    }
}
