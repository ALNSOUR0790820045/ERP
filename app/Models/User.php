<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'email',
        'password',
        'username',
        'phone',
        'employee_id',
        'branch_id',
        'role_id',
        'language',
        'timezone',
        'is_active',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * علاقة الدور
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * علاقة الفرع
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * علاقة الموظف
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * التحقق من صلاحية معينة
     */
    public function hasPermission(string $permissionCode): bool
    {
        // مدير النظام لديه كل الصلاحيات
        if ($this->role?->code === 'super_admin') {
            return true;
        }

        return $this->role?->hasPermission($permissionCode) ?? false;
    }

    /**
     * التحقق من أي صلاحية من مجموعة
     */
    public function hasAnyPermission(array $permissionCodes): bool
    {
        foreach ($permissionCodes as $code) {
            if ($this->hasPermission($code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * التحقق من جميع الصلاحيات
     */
    public function hasAllPermissions(array $permissionCodes): bool
    {
        foreach ($permissionCodes as $code) {
            if (!$this->hasPermission($code)) {
                return false;
            }
        }
        return true;
    }

    /**
     * الحصول على الاسم المعروض
     */
    public function getDisplayNameAttribute(): string
    {
        if (app()->getLocale() === 'ar' && $this->name_ar) {
            return $this->name_ar;
        }
        return $this->name_en ?? $this->name;
    }

    /**
     * هل المستخدم مدير نظام
     */
    public function isSuperAdmin(): bool
    {
        return $this->role?->code === 'super_admin';
    }

    /**
     * هل المستخدم مدير شركة
     */
    public function isCompanyAdmin(): bool
    {
        return $this->role?->code === 'company_admin';
    }
}
