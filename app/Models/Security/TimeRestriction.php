<?php

namespace App\Models\Security;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class TimeRestriction extends Model
{
    protected $table = 'time_restrictions';

    protected $fillable = [
        'name',
        'user_id',
        'role_id',
        'restriction_type',
        'days_of_week',
        'start_time',
        'end_time',
        'timezone',
        'effective_from',
        'effective_until',
        'applies_to_all',
        'is_active',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'effective_from' => 'date',
        'effective_until' => 'date',
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

    public function scopeEffective(Builder $query): Builder
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('effective_from')
              ->orWhere('effective_from', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('effective_until')
              ->orWhere('effective_until', '>=', $now);
        });
    }

    public function scopeAllowed(Builder $query): Builder
    {
        return $query->where('restriction_type', 'allowed');
    }

    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('restriction_type', 'blocked');
    }

    // التحقق من أن الوقت الحالي يقع ضمن القيد
    public function matchesCurrentTime(?Carbon $time = null): bool
    {
        $time = $time ?? Carbon::now($this->timezone);
        
        // تحقق من اليوم
        $dayOfWeek = $time->dayOfWeekIso; // 1 = Monday, 7 = Sunday
        if (!in_array($dayOfWeek, $this->days_of_week ?? [])) {
            return false;
        }

        // تحقق من الوقت
        $currentTime = $time->format('H:i:s');
        $startTime = Carbon::parse($this->start_time)->format('H:i:s');
        $endTime = Carbon::parse($this->end_time)->format('H:i:s');

        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    // التحقق من الفعالية
    public function isEffective(): bool
    {
        $now = now();

        if ($this->effective_from && $this->effective_from->isFuture()) {
            return false;
        }

        if ($this->effective_until && $this->effective_until->isPast()) {
            return false;
        }

        return true;
    }

    // التحقق من السماح للمستخدم
    public static function isAccessAllowed(?int $userId = null, ?int $roleId = null): bool
    {
        $now = Carbon::now();

        // فحص قيود الحظر أولاً
        $blocked = static::active()
            ->effective()
            ->blocked()
            ->where(function ($q) use ($userId, $roleId) {
                $q->where('applies_to_all', true);
                if ($userId) $q->orWhere('user_id', $userId);
                if ($roleId) $q->orWhere('role_id', $roleId);
            })
            ->get();

        foreach ($blocked as $restriction) {
            if ($restriction->matchesCurrentTime($now)) {
                return false;
            }
        }

        // فحص قيود السماح
        $allowed = static::active()
            ->effective()
            ->allowed()
            ->where(function ($q) use ($userId, $roleId) {
                $q->where('applies_to_all', true);
                if ($userId) $q->orWhere('user_id', $userId);
                if ($roleId) $q->orWhere('role_id', $roleId);
            })
            ->get();

        // إذا لا توجد قيود سماح، السماح
        if ($allowed->isEmpty()) {
            return true;
        }

        // يجب أن يتطابق مع إحدى القواعد
        foreach ($allowed as $restriction) {
            if ($restriction->matchesCurrentTime($now)) {
                return true;
            }
        }

        return false;
    }

    // الحصول على الأيام بأسماء عربية
    public function getDaysNamesAttribute(): array
    {
        $dayNames = [
            1 => 'الاثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت',
            7 => 'الأحد',
        ];

        return array_map(fn($day) => $dayNames[$day] ?? $day, $this->days_of_week ?? []);
    }
}
