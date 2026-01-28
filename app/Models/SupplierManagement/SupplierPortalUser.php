<?php

namespace App\Models\SupplierManagement;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class SupplierPortalUser extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'supplier_id', 'name', 'email', 'password', 'phone', 'position', 'role',
        'is_primary', 'is_active', 'email_verified_at', 'last_login_at', 'last_login_ip', 'permissions',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime', 'last_login_at' => 'datetime',
        'is_primary' => 'boolean', 'is_active' => 'boolean', 'permissions' => 'array', 'password' => 'hashed',
    ];

    const ROLE_ADMIN = 'admin';
    const ROLE_USER = 'user';
    const ROLE_VIEWER = 'viewer';

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function notifications(): HasMany { return $this->hasMany(SupplierNotification::class, 'portal_user_id'); }
    public function messages(): HasMany { return $this->hasMany(SupplierMessage::class, 'portal_user_id'); }

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopePrimary($q) { return $q->where('is_primary', true); }

    public function hasPermission(string $permission): bool {
        return $this->permissions && in_array($permission, $this->permissions);
    }

    public function recordLogin(?string $ip = null): void {
        $this->update(['last_login_at' => now(), 'last_login_ip' => $ip ?? request()->ip()]);
    }
}
