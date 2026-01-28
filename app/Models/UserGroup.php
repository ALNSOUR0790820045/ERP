<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UserGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_group_members')
            ->withTimestamps();
    }

    public function addMember(User $user): void
    {
        $this->members()->syncWithoutDetaching([$user->id]);
    }

    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);
    }

    public function getMemberIds(): array
    {
        return $this->members()->pluck('users.id')->toArray();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
