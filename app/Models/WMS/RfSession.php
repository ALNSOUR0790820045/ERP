<?php

namespace App\Models\WMS;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RfSession extends Model
{
    protected $table = 'wms_rf_sessions';

    protected $fillable = [
        'device_id', 'user_id', 'warehouse_id', 'login_at', 'logout_at',
        'status', 'app_version', 'permissions', 'last_activity_at',
        'total_scans', 'successful_scans', 'failed_scans',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'permissions' => 'array',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_IDLE = 'idle';
    const STATUS_ENDED = 'ended';
    const STATUS_TIMEOUT = 'timeout';

    public function device(): BelongsTo
    {
        return $this->belongsTo(RfDevice::class, 'device_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class, 'session_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public static function start(RfDevice $device, User $user, Warehouse $warehouse, array $data = []): self
    {
        // End any existing session for this device
        static::where('device_id', $device->id)
            ->where('status', self::STATUS_ACTIVE)
            ->update(['status' => self::STATUS_ENDED, 'logout_at' => now()]);

        return static::create([
            'device_id' => $device->id,
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'login_at' => now(),
            'status' => self::STATUS_ACTIVE,
            'app_version' => $data['app_version'] ?? null,
            'permissions' => $data['permissions'] ?? null,
            'last_activity_at' => now(),
        ]);
    }

    public function end(): void
    {
        $this->update([
            'status' => self::STATUS_ENDED,
            'logout_at' => now(),
        ]);
        
        $this->device->release();
    }

    public function recordScan(bool $success = true): void
    {
        $this->increment('total_scans');
        $this->increment($success ? 'successful_scans' : 'failed_scans');
        $this->update(['last_activity_at' => now()]);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getDuration(): int
    {
        $end = $this->logout_at ?? now();
        return $this->login_at->diffInMinutes($end);
    }

    public function getSuccessRate(): float
    {
        if ($this->total_scans === 0) return 100;
        return round(($this->successful_scans / $this->total_scans) * 100, 2);
    }
}
