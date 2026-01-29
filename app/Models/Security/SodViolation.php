<?php

namespace App\Models\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SodViolation extends Model
{
    protected $table = 'sod_violations';

    protected $fillable = [
        'sod_rule_id',
        'user_id',
        'violation_type',
        'conflicting_actions',
        'status',
        'description',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'resolution',
    ];

    protected $casts = [
        'conflicting_actions' => 'array',
        'reviewed_at' => 'datetime',
    ];

    // العلاقات
    public function rule(): BelongsTo
    {
        return $this->belongsTo(SodRule::class, 'sod_rule_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes
    public function scopeDetected(Builder $query): Builder
    {
        return $query->where('status', 'detected');
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->whereIn('status', ['detected']);
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'resolved');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // مراجعة الانتهاك
    public function review(int $reviewedBy, string $notes, string $status = 'reviewed'): bool
    {
        return $this->update([
            'status' => $status,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    // إعفاء الانتهاك
    public function exempt(int $reviewedBy, string $notes): bool
    {
        return $this->review($reviewedBy, $notes, 'exempted');
    }

    // حل الانتهاك
    public function resolve(int $reviewedBy, string $resolution): bool
    {
        return $this->update([
            'status' => 'resolved',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
            'resolution' => $resolution,
        ]);
    }

    // تسجيل انتهاك جديد
    public static function record(SodRule $rule, int $userId, array $actions, string $description): self
    {
        return static::create([
            'sod_rule_id' => $rule->id,
            'user_id' => $userId,
            'violation_type' => $rule->rule_type,
            'conflicting_actions' => $actions,
            'status' => 'detected',
            'description' => $description,
        ]);
    }
}
