<?php

namespace App\Models\Security;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class IpRestriction extends Model
{
    protected $table = 'ip_restrictions';

    protected $fillable = [
        'name',
        'user_id',
        'role_id',
        'restriction_type',
        'ip_address',
        'ip_range_start',
        'ip_range_end',
        'cidr',
        'country_code',
        'applies_to_all',
        'reason',
        'is_active',
    ];

    protected $casts = [
        'applies_to_all' => 'boolean',
        'is_active' => 'boolean',
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

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWhitelist(Builder $query): Builder
    {
        return $query->where('restriction_type', 'whitelist');
    }

    public function scopeBlacklist(Builder $query): Builder
    {
        return $query->where('restriction_type', 'blacklist');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere('applies_to_all', true);
        });
    }

    // التحقق من تطابق IP
    public function matchesIp(string $ip): bool
    {
        // تطابق مباشر
        if ($this->ip_address && $this->ip_address === $ip) {
            return true;
        }

        // تطابق نطاق
        if ($this->ip_range_start && $this->ip_range_end) {
            $ipLong = ip2long($ip);
            $startLong = ip2long($this->ip_range_start);
            $endLong = ip2long($this->ip_range_end);
            
            if ($ipLong >= $startLong && $ipLong <= $endLong) {
                return true;
            }
        }

        // تطابق CIDR
        if ($this->cidr) {
            return $this->ipMatchesCidr($ip, $this->cidr);
        }

        return false;
    }

    // التحقق من CIDR
    protected function ipMatchesCidr(string $ip, string $cidr): bool
    {
        list($subnet, $bits) = explode('/', $cidr);
        
        if ($bits === null) $bits = 32;
        
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        
        return ($ip & $mask) === $subnet;
    }

    // التحقق من السماح لمستخدم
    public static function isIpAllowed(string $ip, ?int $userId = null, ?int $roleId = null): bool
    {
        // فحص القائمة السوداء أولاً
        $blacklisted = static::active()
            ->blacklist()
            ->where(function ($q) use ($userId, $roleId) {
                $q->where('applies_to_all', true);
                if ($userId) $q->orWhere('user_id', $userId);
                if ($roleId) $q->orWhere('role_id', $roleId);
            })
            ->get();

        foreach ($blacklisted as $restriction) {
            if ($restriction->matchesIp($ip)) {
                return false;
            }
        }

        // فحص القائمة البيضاء
        $whitelisted = static::active()
            ->whitelist()
            ->where(function ($q) use ($userId, $roleId) {
                $q->where('applies_to_all', true);
                if ($userId) $q->orWhere('user_id', $userId);
                if ($roleId) $q->orWhere('role_id', $roleId);
            })
            ->get();

        // إذا لا توجد قيود قائمة بيضاء، السماح
        if ($whitelisted->isEmpty()) {
            return true;
        }

        // يجب أن يتطابق مع إحدى القواعد
        foreach ($whitelisted as $restriction) {
            if ($restriction->matchesIp($ip)) {
                return true;
            }
        }

        return false;
    }
}
