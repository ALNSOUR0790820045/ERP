<?php

namespace App\Models\Notification;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class EscalationRule extends Model
{
    protected $table = 'escalation_rules';

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'entity_type',
        'trigger_condition',
        'conditions',
        'initial_wait_hours',
        'max_escalations',
        'notify_requester',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'notify_requester' => 'boolean',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function levels(): HasMany
    {
        return $this->hasMany(EscalationLevel::class, 'rule_id')->orderBy('level');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EscalationLog::class, 'rule_id');
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

    // الحصول على المستوى التالي
    public function getNextLevel(int $currentLevel): ?EscalationLevel
    {
        return $this->levels()->where('level', '>', $currentLevel)->first();
    }

    // التحقق من الشروط
    public function matchesConditions(array $data): bool
    {
        if (!$this->conditions) return true;

        foreach ($this->conditions as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'];

            $dataValue = $data[$field] ?? null;

            $matched = match ($operator) {
                '=' => $dataValue == $value,
                '!=' => $dataValue != $value,
                '>' => $dataValue > $value,
                '<' => $dataValue < $value,
                '>=' => $dataValue >= $value,
                '<=' => $dataValue <= $value,
                'in' => in_array($dataValue, (array) $value),
                'not_in' => !in_array($dataValue, (array) $value),
                default => false,
            };

            if (!$matched) return false;
        }

        return true;
    }

    // بدء التصعيد
    public function startEscalation($escalatable): EscalationLog
    {
        $firstLevel = $this->levels()->first();

        return EscalationLog::create([
            'rule_id' => $this->id,
            'escalatable_type' => get_class($escalatable),
            'escalatable_id' => $escalatable->id,
            'current_level' => $firstLevel ? $firstLevel->level : 1,
            'escalated_to_user_id' => $firstLevel?->escalate_to_user_id,
            'escalated_to_role_id' => $firstLevel?->escalate_to_role_id,
            'status' => 'escalated',
            'escalated_at' => now(),
        ]);
    }
}
