<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ربط المستخدم بالأدوار المتعددة
 * User can have multiple roles (job roles + tender roles)
 */
class UserRole extends Model
{
    protected $fillable = [
        'user_id',
        'role_id',
        'is_primary',
        'assigned_at',
        'assigned_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->whereHas('role', fn($q) => $q->where('type', $type));
    }
}
