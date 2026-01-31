<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionTemplate extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description',
        'module_id',
        'permissions',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * الوحدة المرتبطة بالقالب
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * الحصول على اسم القالب حسب اللغة
     */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?? $this->name_ar);
    }

    /**
     * الحصول على القوالب النشطة
     */
    public static function getActiveTemplates()
    {
        return self::where('is_active', true)->get();
    }

    /**
     * الحصول على قوالب وحدة معينة
     */
    public static function getModuleTemplates(int $moduleId)
    {
        return self::where('module_id', $moduleId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * تطبيق القالب على مستخدم
     */
    public function applyToUser(int $userId, ?int $grantedBy = null): void
    {
        if (!$this->permissions || !is_array($this->permissions)) {
            return;
        }

        foreach ($this->permissions as $stageCode => $permissionCodes) {
            $stage = ModuleStage::where('module_id', $this->module_id)
                ->where('code', $stageCode)
                ->first();

            if (!$stage) {
                continue;
            }

            foreach ($permissionCodes as $permissionCode => $settings) {
                $permissionType = PermissionType::where('code', $permissionCode)->first();

                if (!$permissionType) {
                    continue;
                }

                UserStagePermission::grantPermission(
                    $userId,
                    $this->module_id,
                    $stage->id,
                    $permissionType->id,
                    $grantedBy,
                    null, // لا تنتهي
                    $settings['can_view_stage'] ?? true,
                    "تم التطبيق من قالب: {$this->name_ar}"
                );
            }
        }
    }

    /**
     * تطبيق القالب على دور
     */
    public function applyToRole(int $roleId): void
    {
        if (!$this->permissions || !is_array($this->permissions)) {
            return;
        }

        foreach ($this->permissions as $stageCode => $permissionCodes) {
            $stage = ModuleStage::where('module_id', $this->module_id)
                ->where('code', $stageCode)
                ->first();

            if (!$stage) {
                continue;
            }

            foreach ($permissionCodes as $permissionCode => $settings) {
                $permissionType = PermissionType::where('code', $permissionCode)->first();

                if (!$permissionType) {
                    continue;
                }

                RoleStagePermission::grantPermission(
                    $roleId,
                    $this->module_id,
                    $stage->id,
                    $permissionType->id,
                    $settings['can_view_stage'] ?? true
                );
            }
        }
    }

    /**
     * إنشاء قالب من صلاحيات مستخدم حالية
     */
    public static function createFromUserPermissions(
        int $userId,
        int $moduleId,
        string $code,
        string $nameAr,
        ?string $nameEn = null,
        ?string $description = null
    ): self {
        $userPermissions = UserStagePermission::where('user_id', $userId)
            ->where('module_id', $moduleId)
            ->with(['stage', 'permissionType'])
            ->get();

        $permissions = [];
        foreach ($userPermissions as $permission) {
            $stageCode = $permission->stage->code;
            $permissionCode = $permission->permissionType->code;

            if (!isset($permissions[$stageCode])) {
                $permissions[$stageCode] = [];
            }

            $permissions[$stageCode][$permissionCode] = [
                'can_view_stage' => $permission->can_view_stage,
            ];
        }

        return self::create([
            'code' => $code,
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'description' => $description,
            'module_id' => $moduleId,
            'permissions' => $permissions,
            'is_active' => true,
        ]);
    }

    /**
     * قوالب العطاءات الافتراضية
     */
    public static function getTenderTemplates(): array
    {
        return [
            'tender_secretary' => [
                'name_ar' => 'سكرتير عطاءات',
                'name_en' => 'Tender Secretary',
                'description' => 'يمكنه إضافة العطاءات والتعامل مع الشراء',
                'permissions' => [
                    'monitoring' => [
                        'view' => ['can_view_stage' => true],
                        'create' => ['can_view_stage' => true],
                        'update' => ['can_view_stage' => true],
                    ],
                    'decision' => [
                        'view' => ['can_view_stage' => true],
                    ],
                    'purchase' => [
                        'view' => ['can_view_stage' => true],
                        'create' => ['can_view_stage' => true],
                        'update' => ['can_view_stage' => true],
                    ],
                ],
            ],
            'tender_manager' => [
                'name_ar' => 'مدير عطاءات',
                'name_en' => 'Tender Manager',
                'description' => 'صلاحيات كاملة على جميع مراحل العطاءات',
                'permissions' => [
                    'monitoring' => [
                        'view' => ['can_view_stage' => true],
                        'create' => ['can_view_stage' => true],
                        'update' => ['can_view_stage' => true],
                        'delete' => ['can_view_stage' => true],
                    ],
                    'study' => [
                        'view' => ['can_view_stage' => true],
                        'update' => ['can_view_stage' => true],
                        'approve' => ['can_view_stage' => true],
                        'reject' => ['can_view_stage' => true],
                    ],
                    'decision' => [
                        'view' => ['can_view_stage' => true],
                        'update' => ['can_view_stage' => true],
                        'approve' => ['can_view_stage' => true],
                        'reject' => ['can_view_stage' => true],
                    ],
                    'purchase' => [
                        'view' => ['can_view_stage' => true],
                        'create' => ['can_view_stage' => true],
                        'update' => ['can_view_stage' => true],
                    ],
                    'pricing' => [
                        'view' => ['can_view_stage' => true],
                        'create' => ['can_view_stage' => true],
                        'update' => ['can_view_stage' => true],
                    ],
                    'submission' => [
                        'view' => ['can_view_stage' => true],
                        'create' => ['can_view_stage' => true],
                        'update' => ['can_view_stage' => true],
                    ],
                ],
            ],
            'tender_viewer' => [
                'name_ar' => 'مشاهد عطاءات',
                'name_en' => 'Tender Viewer',
                'description' => 'يمكنه مشاهدة جميع المراحل فقط',
                'permissions' => [
                    'monitoring' => [
                        'view' => ['can_view_stage' => true],
                    ],
                    'study' => [
                        'view' => ['can_view_stage' => true],
                    ],
                    'decision' => [
                        'view' => ['can_view_stage' => true],
                    ],
                    'purchase' => [
                        'view' => ['can_view_stage' => true],
                    ],
                    'pricing' => [
                        'view' => ['can_view_stage' => true],
                    ],
                    'submission' => [
                        'view' => ['can_view_stage' => true],
                    ],
                ],
            ],
        ];
    }
}
