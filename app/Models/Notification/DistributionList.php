<?php

namespace App\Models\Notification;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class DistributionList extends Model
{
    protected $table = 'distribution_lists';

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description',
        'list_type',
        'dynamic_query',
        'owner_id',
        'allow_external',
        'is_active',
    ];

    protected $casts = [
        'dynamic_query' => 'array',
        'allow_external' => 'boolean',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(DistributionListMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('is_active', true);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeStatic(Builder $query): Builder
    {
        return $query->where('list_type', 'static');
    }

    public function scopeDynamic(Builder $query): Builder
    {
        return $query->where('list_type', 'dynamic');
    }

    // الحصول على جميع المستلمين
    public function getRecipients(): array
    {
        $recipients = [];

        if ($this->list_type === 'static') {
            foreach ($this->activeMembers as $member) {
                if ($member->user_id && $member->user) {
                    $recipients[] = [
                        'type' => 'user',
                        'user' => $member->user,
                        'email' => $member->user->email,
                    ];
                } elseif ($member->role_id && $member->role) {
                    foreach ($member->role->users ?? [] as $user) {
                        $recipients[] = [
                            'type' => 'user',
                            'user' => $user,
                            'email' => $user->email,
                        ];
                    }
                } elseif ($member->external_email) {
                    $recipients[] = [
                        'type' => 'external',
                        'email' => $member->external_email,
                        'name' => $member->external_name,
                        'phone' => $member->external_phone,
                    ];
                }
            }
        } else {
            // قائمة ديناميكية
            $recipients = $this->executeDynamicQuery();
        }

        return $recipients;
    }

    // تنفيذ الاستعلام الديناميكي
    protected function executeDynamicQuery(): array
    {
        if (!$this->dynamic_query) return [];

        $query = User::query();

        foreach ($this->dynamic_query as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'];

            $query->where($field, $operator, $value);
        }

        return $query->get()->map(function ($user) {
            return [
                'type' => 'user',
                'user' => $user,
                'email' => $user->email,
            ];
        })->toArray();
    }

    // الحصول على عناوين البريد
    public function getEmails(): array
    {
        return array_column($this->getRecipients(), 'email');
    }

    // عدد الأعضاء
    public function getMemberCountAttribute(): int
    {
        if ($this->list_type === 'static') {
            return $this->activeMembers()->count();
        }
        return count($this->executeDynamicQuery());
    }
}
