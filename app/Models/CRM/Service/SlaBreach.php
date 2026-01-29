<?php

namespace App\Models\CRM\Service;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaBreach extends Model
{
    protected $fillable = [
        'case_id',
        'sla_policy_id',
        'breach_type',
        'due_at',
        'breached_at',
        'breach_minutes',
        'reason',
        'acknowledged_by',
        'acknowledged_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'breached_at' => 'datetime',
        'breach_minutes' => 'integer',
        'acknowledged_at' => 'datetime',
    ];

    // العلاقات
    public function case(): BelongsTo
    {
        return $this->belongsTo(ServiceCase::class, 'case_id');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    // Scopes
    public function scopeUnacknowledged($query)
    {
        return $query->whereNull('acknowledged_by');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('breach_type', $type);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('breached_at', '>=', now()->subDays($days));
    }

    // Methods
    public function acknowledge(int $userId, ?string $reason = null): void
    {
        $this->update([
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
            'reason' => $reason,
        ]);
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_by !== null;
    }

    public function getBreachDuration(): string
    {
        $hours = floor($this->breach_minutes / 60);
        $minutes = $this->breach_minutes % 60;
        
        if ($hours > 0) {
            return "{$hours} ساعة و {$minutes} دقيقة";
        }
        
        return "{$minutes} دقيقة";
    }

    // إحصائيات
    public static function getBreachRate(int $days = 30): float
    {
        $totalCases = ServiceCase::where('created_at', '>=', now()->subDays($days))->count();
        $breachedCases = static::recent($days)->distinct('case_id')->count('case_id');
        
        return $totalCases > 0 ? ($breachedCases / $totalCases) * 100 : 0;
    }

    public static function getAverageBreachMinutes(int $days = 30): float
    {
        return static::recent($days)->avg('breach_minutes') ?? 0;
    }
}
