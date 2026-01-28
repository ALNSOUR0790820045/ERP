<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditTrail extends Model
{
    use HasFactory;

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'module',
        'notes',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    // الثوابت
    public const ACTIONS = [
        'create' => 'إنشاء',
        'update' => 'تحديث',
        'delete' => 'حذف',
        'view' => 'عرض',
        'approve' => 'اعتماد',
        'reject' => 'رفض',
        'print' => 'طباعة',
        'export' => 'تصدير',
        'import' => 'استيراد',
        'login' => 'تسجيل دخول',
        'logout' => 'تسجيل خروج',
    ];

    public function getActionLabelAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    /**
     * تسجيل حدث التدقيق
     */
    public static function log(
        string $action,
        $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $notes = null
    ): self {
        return self::create([
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'module' => self::detectModule(),
            'notes' => $notes,
        ]);
    }

    /**
     * اكتشاف الوحدة النمطية
     */
    protected static function detectModule(): ?string
    {
        $url = request()->path();
        $segments = explode('/', $url);
        
        return $segments[1] ?? $segments[0] ?? null;
    }
}
