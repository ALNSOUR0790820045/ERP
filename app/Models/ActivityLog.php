<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabel(): string
    {
        return match($this->action) {
            'created' => 'إنشاء',
            'updated' => 'تحديث',
            'deleted' => 'حذف',
            'restored' => 'استعادة',
            'login' => 'تسجيل دخول',
            'logout' => 'تسجيل خروج',
            'viewed' => 'عرض',
            'exported' => 'تصدير',
            'imported' => 'استيراد',
            'approved' => 'اعتماد',
            'rejected' => 'رفض',
            default => $this->action,
        };
    }

    public function getModelName(): string
    {
        $map = [
            'App\\Models\\Tender' => 'عطاء',
            'App\\Models\\Contract' => 'عقد',
            'App\\Models\\Project' => 'مشروع',
            'App\\Models\\User' => 'مستخدم',
            'App\\Models\\Invoice' => 'فاتورة',
        ];

        return $map[$this->model_type] ?? class_basename($this->model_type);
    }

    public function getChanges(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    public static function log(
        string $action,
        ?Model $model = null,
        array $oldValues = [],
        array $newValues = []
    ): self {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    public function scopeForModel($query, string $modelType, ?int $modelId = null)
    {
        $query->where('model_type', $modelType);
        
        if ($modelId) {
            $query->where('model_id', $modelId);
        }
        
        return $query;
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
