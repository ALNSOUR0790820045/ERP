<?php

namespace App\Models\WMS;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RfDevice extends Model
{
    protected $table = 'wms_rf_devices';

    protected $fillable = [
        'device_code', 'device_name', 'device_type', 'manufacturer', 'model',
        'serial_number', 'mac_address', 'ip_address', 'warehouse_id', 'assigned_user_id',
        'status', 'battery_level', 'capabilities', 'settings',
        'last_activity_at', 'last_sync_at', 'purchase_date', 'warranty_expiry', 'is_active',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'settings' => 'array',
        'last_activity_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'is_active' => 'boolean',
    ];

    const TYPE_HANDHELD = 'handheld';
    const TYPE_FORKLIFT = 'forklift';
    const TYPE_WEARABLE = 'wearable';
    const TYPE_TABLET = 'tablet';
    const TYPE_FIXED = 'fixed';

    const STATUS_AVAILABLE = 'available';
    const STATUS_IN_USE = 'in_use';
    const STATUS_CHARGING = 'charging';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_LOST = 'lost';

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(RfSession::class, 'device_id');
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class, 'device_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE)->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE && $this->is_active;
    }

    public function assignTo(User $user): void
    {
        $this->update([
            'assigned_user_id' => $user->id,
            'status' => self::STATUS_IN_USE,
        ]);
    }

    public function release(): void
    {
        $this->update([
            'assigned_user_id' => null,
            'status' => self::STATUS_AVAILABLE,
        ]);
    }

    public function updateBattery(int $level): void
    {
        $this->update([
            'battery_level' => $level,
            'last_activity_at' => now(),
        ]);
    }

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? []);
    }

    public function activeSession(): ?RfSession
    {
        return $this->sessions()->where('status', 'active')->first();
    }
}
