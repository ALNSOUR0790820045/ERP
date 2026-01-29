<?php

namespace App\Models\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Delegation extends Model
{
    protected $table = 'delegations';

    protected $fillable = [
        'delegation_code',
        'delegator_id',
        'delegate_id',
        'delegation_type',
        'status',
        'start_date',
        'end_date',
        'reason',
        'delegated_permissions',
        'excluded_permissions',
        'amount_limit',
        'can_sub_delegate',
        'requires_approval',
        'approved_by',
        'approved_at',
        'revoked_by',
        'revoked_at',
        'revocation_reason',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'delegated_permissions' => 'array',
        'excluded_permissions' => 'array',
        'amount_limit' => 'decimal:2',
        'can_sub_delegate' => 'boolean',
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    // العلاقات
    public function delegator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegator_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function revokedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeForDelegator(Builder $query, int $userId): Builder
    {
        return $query->where('delegator_id', $userId);
    }

    public function scopeForDelegate(Builder $query, int $userId): Builder
    {
        return $query->where('delegate_id', $userId);
    }

    // التحقق من أن التفويض نشط
    public function isActive(): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->start_date && $this->start_date->isFuture()) return false;
        if ($this->end_date && $this->end_date->isPast()) return false;
        return true;
    }

    // الموافقة على التفويض
    public function approve(int $approvedBy): bool
    {
        return $this->update([
            'status' => 'active',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    // رفض التفويض
    public function reject(int $rejectedBy, string $reason): bool
    {
        return $this->update([
            'status' => 'rejected',
            'revoked_by' => $rejectedBy,
            'revoked_at' => now(),
            'revocation_reason' => $reason,
        ]);
    }

    // إلغاء التفويض
    public function revoke(int $revokedBy, string $reason): bool
    {
        return $this->update([
            'status' => 'revoked',
            'revoked_by' => $revokedBy,
            'revoked_at' => now(),
            'revocation_reason' => $reason,
        ]);
    }

    // التحقق من صلاحية محددة
    public function hasPermission(string $permission): bool
    {
        if (!$this->isActive()) return false;

        // إذا كان تفويض كامل
        if ($this->delegation_type === 'full') {
            // تحقق من الاستثناءات
            if ($this->excluded_permissions && in_array($permission, $this->excluded_permissions)) {
                return false;
            }
            return true;
        }

        // تفويض جزئي أو محدد
        return $this->delegated_permissions && in_array($permission, $this->delegated_permissions);
    }

    // التحقق من حد المبلغ
    public function isWithinAmountLimit(float $amount): bool
    {
        if (!$this->amount_limit) return true;
        return $amount <= $this->amount_limit;
    }

    // توليد رمز التفويض
    public static function generateCode(): string
    {
        return 'DEL-' . date('Y') . '-' . str_pad(static::whereYear('created_at', date('Y'))->count() + 1, 5, '0', STR_PAD_LEFT);
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->delegation_code)) {
                $model->delegation_code = static::generateCode();
            }
        });
    }
}
