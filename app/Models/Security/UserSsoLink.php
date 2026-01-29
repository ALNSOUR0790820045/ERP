<?php

namespace App\Models\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSsoLink extends Model
{
    protected $table = 'user_sso_links';

    protected $fillable = [
        'user_id',
        'sso_provider_id',
        'external_id',
        'external_email',
        'external_data',
        'linked_at',
        'last_login_at',
    ];

    protected $casts = [
        'external_data' => 'array',
        'linked_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ssoProvider(): BelongsTo
    {
        return $this->belongsTo(SsoProvider::class, 'sso_provider_id');
    }

    // تحديث آخر تسجيل دخول
    public function recordLogin(): bool
    {
        return $this->update(['last_login_at' => now()]);
    }

    // البحث عن رابط
    public static function findByExternalId(int $providerId, string $externalId): ?self
    {
        return static::where('sso_provider_id', $providerId)
            ->where('external_id', $externalId)
            ->first();
    }

    // إنشاء أو تحديث رابط
    public static function link(User $user, SsoProvider $provider, string $externalId, array $externalData = []): self
    {
        return static::updateOrCreate(
            [
                'user_id' => $user->id,
                'sso_provider_id' => $provider->id,
            ],
            [
                'external_id' => $externalId,
                'external_email' => $externalData['email'] ?? null,
                'external_data' => $externalData,
                'linked_at' => now(),
            ]
        );
    }
}
