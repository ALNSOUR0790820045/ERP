<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TemporaryPermission extends Model
{
    protected $fillable = [
        'user_id',
        'module_id',
        'stage_id',
        'permission_type_id',
        'permissionable_type',
        'permissionable_id',
        'reason',
        'granted_by',
        'expires_at',
        'used_at',
        'is_revoked',
        'revoked_by',
        'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'is_revoked' => 'boolean',
        'revoked_at' => 'datetime',
    ];

    /**
     * المستخدم صاحب الصلاحية المؤقتة
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
     * الكائن المرتبط بالصلاحية (مثلاً: عطاء معين)
     */
    public function permissionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * المستخدم الذي منح الصلاحية
     */
    public function grantedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * المستخدم الذي ألغى الصلاحية
     */
    public function revokedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * هل الصلاحية صالحة؟
     */
    public function isValid(): bool
    {
        // تم إلغاؤها
        if ($this->is_revoked) {
            return false;
        }

        // تم استخدامها
        if ($this->used_at) {
            return false;
        }

        // منتهية الصلاحية
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * تحديد كمستخدمة
     */
    public function markAsUsed(): self
    {
        $this->update(['used_at' => now()]);
        return $this;
    }

    /**
     * إلغاء الصلاحية
     */
    public function revoke(?int $revokedBy = null): self
    {
        $this->update([
            'is_revoked' => true,
            'revoked_by' => $revokedBy ?? auth()->id(),
            'revoked_at' => now(),
        ]);
        return $this;
    }

    /**
     * منح صلاحية مؤقتة
     */
    public static function grant(
        int $userId,
        int $moduleId,
        int $stageId,
        int $permissionTypeId,
        string $reason,
        ?string $expiresAt = null,
        ?string $permissionableType = null,
        ?int $permissionableId = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'module_id' => $moduleId,
            'stage_id' => $stageId,
            'permission_type_id' => $permissionTypeId,
            'reason' => $reason,
            'granted_by' => auth()->id(),
            'expires_at' => $expiresAt ?? now()->addHours(24),
            'permissionable_type' => $permissionableType,
            'permissionable_id' => $permissionableId,
        ]);
    }

    /**
     * التحقق من وجود صلاحية مؤقتة صالحة
     */
    public static function hasValidPermission(
        int $userId,
        int $stageId,
        string $permissionCode,
        ?string $permissionableType = null,
        ?int $permissionableId = null
    ): bool {
        $query = self::where('user_id', $userId)
            ->where('stage_id', $stageId)
            ->where('is_revoked', false)
            ->whereNull('used_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->whereHas('permissionType', function ($q) use ($permissionCode) {
                $q->where('code', $permissionCode);
            });

        if ($permissionableType && $permissionableId) {
            $query->where('permissionable_type', $permissionableType)
                ->where('permissionable_id', $permissionableId);
        }

        return $query->exists();
    }

    /**
     * الحصول على الصلاحيات المؤقتة الصالحة لمستخدم
     */
    public static function getValidPermissions(int $userId)
    {
        return self::where('user_id', $userId)
            ->where('is_revoked', false)
            ->whereNull('used_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with(['module', 'stage', 'permissionType'])
            ->get();
    }

    /**
     * منح صلاحية تعديل مؤقتة عند إرجاع العطاء
     */
    public static function grantTenderReturnPermission(
        int $userId,
        int $tenderId,
        string $reason
    ): self {
        // البحث عن وحدة العطاءات
        $module = Module::where('code', 'tenders')->first();
        
        // البحث عن مرحلة الرصد
        $stage = ModuleStage::where('module_id', $module->id)
            ->where('code', 'monitoring')
            ->first();

        // البحث عن صلاحية التعديل
        $permissionType = PermissionType::where('code', 'update')->first();

        return self::grant(
            $userId,
            $module->id,
            $stage->id,
            $permissionType->id,
            $reason,
            now()->addHours(48), // صالحة لمدة 48 ساعة
            Tender::class,
            $tenderId
        );
    }
}
