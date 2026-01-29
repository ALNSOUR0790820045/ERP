<?php

namespace App\Models\Notification;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class EventSubscription extends Model
{
    protected $table = 'event_subscriptions';

    protected $fillable = [
        'user_id',
        'event_type',
        'entity_type',
        'entity_id',
        'conditions',
        'channels',
        'frequency',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'channels' => 'array',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeForEntity(Builder $query, string $type, ?int $id = null): Builder
    {
        $query->where('entity_type', $type);
        if ($id !== null) {
            $query->where(function ($q) use ($id) {
                $q->whereNull('entity_id')->orWhere('entity_id', $id);
            });
        }
        return $query;
    }

    public function scopeImmediate(Builder $query): Builder
    {
        return $query->where('frequency', 'immediate');
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
                'in' => in_array($dataValue, (array) $value),
                default => false,
            };

            if (!$matched) return false;
        }

        return true;
    }

    // الحصول على المشتركين لحدث معين
    public static function getSubscribers(string $eventType, ?string $entityType = null, ?int $entityId = null, array $data = []): array
    {
        $query = static::active()->forEvent($eventType);

        if ($entityType) {
            $query->forEntity($entityType, $entityId);
        }

        $subscriptions = $query->with('user')->get();
        
        return $subscriptions->filter(function ($sub) use ($data) {
            return $sub->matchesConditions($data);
        })->all();
    }
}
