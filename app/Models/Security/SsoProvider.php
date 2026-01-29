<?php

namespace App\Models\Security;

use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class SsoProvider extends Model
{
    protected $table = 'sso_providers';

    protected $fillable = [
        'code',
        'name',
        'protocol',
        'client_id',
        'client_secret',
        'authorization_url',
        'token_url',
        'userinfo_url',
        'logout_url',
        'scopes',
        'attribute_mapping',
        'certificate_path',
        'metadata',
        'settings',
        'auto_create_user',
        'default_role_id',
        'is_active',
    ];

    protected $casts = [
        'scopes' => 'array',
        'attribute_mapping' => 'array',
        'settings' => 'array',
        'auto_create_user' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'client_secret',
    ];

    // العلاقات
    public function defaultRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'default_role_id');
    }

    public function userLinks(): HasMany
    {
        return $this->hasMany(UserSsoLink::class, 'sso_provider_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByProtocol(Builder $query, string $protocol): Builder
    {
        return $query->where('protocol', $protocol);
    }

    // الحصول على URL الموافقة
    public function getAuthorizationUrl(string $state, string $redirectUri): string
    {
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes ?? ['openid', 'profile', 'email']),
            'state' => $state,
        ];

        return $this->authorization_url . '?' . http_build_query($params);
    }

    // تحويل بيانات المستخدم الخارجية
    public function mapUserData(array $externalData): array
    {
        $mapping = $this->attribute_mapping ?? [
            'email' => 'email',
            'name' => 'name',
            'given_name' => 'first_name',
            'family_name' => 'last_name',
        ];

        $userData = [];
        foreach ($mapping as $externalKey => $internalKey) {
            if (isset($externalData[$externalKey])) {
                $userData[$internalKey] = $externalData[$externalKey];
            }
        }

        return $userData;
    }

    // التحقق من البروتوكول
    public function isOAuth2(): bool
    {
        return in_array($this->protocol, ['oauth2', 'oidc']);
    }

    public function isSaml(): bool
    {
        return $this->protocol === 'saml2';
    }

    public function isLdap(): bool
    {
        return in_array($this->protocol, ['ldap', 'active_directory']);
    }
}
