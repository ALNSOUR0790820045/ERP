<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStagePermission extends Model
{
    protected $fillable = [
        'user_id',
        'module_id',
        'stage_id',
        'permission_type_id',
        'can_view_stage',
        'granted_by',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'can_view_stage' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * المستخدم صاحب الصلاحية
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * المستخدم الذي منح الصلاحية
     */
    public function grantedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * هل الصلاحية منتهية؟
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * هل الصلاحية نشطة؟
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    /**
     * الحصول على صلاحيات مستخدم معين على وحدة معينة
     */
    public static function getUserModulePermissions(int $userId, int $moduleId)
    {
        return self::where('user_id', $userId)
            ->where('module_id', $moduleId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with(['stage', 'permissionType'])
            ->get();
    }

    /**
     * الحصول على صلاحيات مستخدم على مرحلة معينة
     */
    public static function getUserStagePermissions(int $userId, int $stageId)
    {
        return self::where('user_id', $userId)
            ->where('stage_id', $stageId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with('permissionType')
            ->get();
    }

    /**
     * التحقق من وجود صلاحية معينة
     */
    public static function hasPermission(int $userId, int $stageId, string $permissionCode): bool
    {
        return self::where('user_id', $userId)
            ->where('stage_id', $stageId)
            ->whereHas('permissionType', function ($query) use ($permissionCode) {
                $query->where('code', $permissionCode);
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * منح صلاحية لمستخدم
     */
    public static function grantPermission(
        int $userId,
        int $moduleId,
        int $stageId,
        int $permissionTypeId,
        ?int $grantedBy = null,
        ?string $expiresAt = null,
        bool $canViewStage = true,
        ?string $notes = null
    ): self {
        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'module_id' => $moduleId,
                'stage_id' => $stageId,
                'permission_type_id' => $permissionTypeId,
            ],
            [
                'can_view_stage' => $canViewStage,
                'granted_by' => $grantedBy ?? auth()->id(),
                'expires_at' => $expiresAt,
                'notes' => $notes,
            ]
        );
    }

    /**
     * سحب صلاحية من مستخدم
     */
    public static function revokePermission(int $userId, int $stageId, int $permissionTypeId): bool
    {
        return self::where('user_id', $userId)
            ->where('stage_id', $stageId)
            ->where('permission_type_id', $permissionTypeId)
            ->delete() > 0;
    }
}
