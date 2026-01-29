<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class SodRule extends Model
{
    protected $table = 'sod_rules';

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description',
        'rule_type',
        'conflicting_permissions',
        'severity',
        'action_on_violation',
        'exemptions',
        'is_active',
    ];

    protected $casts = [
        'conflicting_permissions' => 'array',
        'exemptions' => 'array',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function violations(): HasMany
    {
        return $this->hasMany(SodViolation::class, 'sod_rule_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeBySeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    // التحقق من التعارض
    public function checkConflict(array $userPermissions): bool
    {
        if (!$this->is_active) return false;

        $conflicts = $this->conflicting_permissions;
        
        if ($this->rule_type === 'conflict') {
            // يجب أن يمتلك المستخدم جميع الصلاحيات المتعارضة
            $hasAll = true;
            foreach ($conflicts as $permission) {
                if (!in_array($permission, $userPermissions)) {
                    $hasAll = false;
                    break;
                }
            }
            return $hasAll;
        }

        return false;
    }

    // التحقق من الإعفاء
    public function isExempted(int $userId, ?int $roleId = null): bool
    {
        if (!$this->exemptions) return false;

        $exemptions = $this->exemptions;

        if (isset($exemptions['users']) && in_array($userId, $exemptions['users'])) {
            return true;
        }

        if ($roleId && isset($exemptions['roles']) && in_array($roleId, $exemptions['roles'])) {
            return true;
        }

        return false;
    }

    // الحصول على الإجراء المطلوب عند الانتهاك
    public function getViolationAction(): string
    {
        return $this->action_on_violation;
    }

    // التحقق من جميع القواعد لمستخدم
    public static function checkAllRules(int $userId, array $permissions, ?int $roleId = null): array
    {
        $violations = [];

        foreach (static::active()->get() as $rule) {
            if ($rule->isExempted($userId, $roleId)) continue;

            if ($rule->checkConflict($permissions)) {
                $violations[] = [
                    'rule' => $rule,
                    'action' => $rule->getViolationAction(),
                ];
            }
        }

        return $violations;
    }
}
