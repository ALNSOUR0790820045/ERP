<?php

namespace App\Models\Notification;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class DistributionListMember extends Model
{
    protected $table = 'distribution_list_members';

    protected $fillable = [
        'distribution_list_id',
        'user_id',
        'role_id',
        'external_email',
        'external_name',
        'external_phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function distributionList(): BelongsTo
    {
        return $this->belongsTo(DistributionList::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeUsers(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    public function scopeRoles(Builder $query): Builder
    {
        return $query->whereNotNull('role_id');
    }

    public function scopeExternal(Builder $query): Builder
    {
        return $query->whereNotNull('external_email');
    }

    // الحصول على الاسم
    public function getNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name;
        }
        if ($this->role) {
            return $this->role->name_ar ?? $this->role->name;
        }
        return $this->external_name ?? $this->external_email;
    }

    // الحصول على البريد
    public function getEmailAttribute(): ?string
    {
        if ($this->user) {
            return $this->user->email;
        }
        return $this->external_email;
    }
}
