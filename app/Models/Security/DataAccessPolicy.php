<?php

namespace App\Models\Security;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class DataAccessPolicy extends Model
{
    protected $table = 'data_access_policies';

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'entity_type',
        'role_id',
        'user_id',
        'access_type',
        'filter_conditions',
        'allowed_fields',
        'hidden_fields',
        'can_export',
        'can_print',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'filter_conditions' => 'array',
        'allowed_fields' => 'array',
        'hidden_fields' => 'array',
        'can_export' => 'boolean',
        'can_print' => 'boolean',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForEntity(Builder $query, string $entityType): Builder
    {
        return $query->where('entity_type', $entityType);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForRole(Builder $query, int $roleId): Builder
    {
        return $query->where('role_id', $roleId);
    }

    // تطبيق الفلتر على الاستعلام
    public function applyFilter(Builder $query): Builder
    {
        if (!$this->filter_conditions) return $query;

        foreach ($this->filter_conditions as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'] ?? '=';
            $value = $this->resolveValue($condition['value']);

            if ($operator === 'in') {
                $query->whereIn($field, (array) $value);
            } elseif ($operator === 'not_in') {
                $query->whereNotIn($field, (array) $value);
            } elseif ($operator === 'null') {
                $query->whereNull($field);
            } elseif ($operator === 'not_null') {
                $query->whereNotNull($field);
            } else {
                $query->where($field, $operator, $value);
            }
        }

        return $query;
    }

    // حل القيم الديناميكية
    protected function resolveValue($value)
    {
        if (!is_string($value)) return $value;

        $user = auth()->user();
        if (!$user) return $value;

        return match ($value) {
            '{user_id}' => $user->id,
            '{department_id}' => $user->department_id ?? null,
            '{branch_id}' => $user->branch_id ?? null,
            '{company_id}' => $user->company_id ?? null,
            default => $value,
        };
    }

    // فلترة الحقول
    public function filterFields(array $data): array
    {
        if ($this->hidden_fields) {
            foreach ($this->hidden_fields as $field) {
                unset($data[$field]);
            }
        }

        if ($this->allowed_fields) {
            $data = array_intersect_key($data, array_flip($this->allowed_fields));
        }

        return $data;
    }

    // الحصول على السياسة للمستخدم
    public static function getPolicyFor(string $entityType, User $user): ?self
    {
        // البحث عن سياسة خاصة بالمستخدم أولاً
        $policy = static::active()
            ->forEntity($entityType)
            ->forUser($user->id)
            ->orderBy('priority', 'desc')
            ->first();

        if ($policy) return $policy;

        // البحث عن سياسة خاصة بالدور
        if ($user->role_id) {
            return static::active()
                ->forEntity($entityType)
                ->forRole($user->role_id)
                ->orderBy('priority', 'desc')
                ->first();
        }

        return null;
    }
}
