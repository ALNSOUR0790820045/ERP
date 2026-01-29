<?php

namespace App\Models\WMS;

use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Printer extends Model
{
    protected $table = 'wms_printers';

    protected $fillable = [
        'code', 'name', 'printer_type', 'model', 'connection_type',
        'ip_address', 'port', 'mac_address', 'warehouse_id', 'location_id',
        'settings', 'status', 'last_ping_at', 'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'last_ping_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    const TYPE_ZEBRA = 'zebra';
    const TYPE_DATAMAX = 'datamax';
    const TYPE_HONEYWELL = 'honeywell';
    const TYPE_BROTHER = 'brother';

    const STATUS_ONLINE = 'online';
    const STATUS_OFFLINE = 'offline';
    const STATUS_ERROR = 'error';
    const STATUS_MAINTENANCE = 'maintenance';

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id');
    }

    public function prints(): HasMany
    {
        return $this->hasMany(BarcodePrint::class, 'printer_id');
    }

    public function scopeOnline($query)
    {
        return $query->where('status', self::STATUS_ONLINE);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isOnline(): bool
    {
        return $this->status === self::STATUS_ONLINE;
    }

    public function ping(): bool
    {
        if (!$this->ip_address) return false;
        
        $result = @fsockopen($this->ip_address, $this->port ?? 9100, $errno, $errstr, 2);
        $online = $result !== false;
        if ($result) fclose($result);
        
        $this->update([
            'status' => $online ? self::STATUS_ONLINE : self::STATUS_OFFLINE,
            'last_ping_at' => now(),
        ]);
        
        return $online;
    }

    public function sendZpl(string $zpl): bool
    {
        if (!$this->isOnline()) return false;
        
        $socket = @fsockopen($this->ip_address, $this->port ?? 9100, $errno, $errstr, 5);
        if (!$socket) return false;
        
        fwrite($socket, $zpl);
        fclose($socket);
        
        return true;
    }

    public function printLabel(LabelTemplate $template, array $data, int $copies = 1): bool
    {
        $zpl = $template->renderZpl($data);
        
        for ($i = 0; $i < $copies; $i++) {
            if (!$this->sendZpl($zpl)) return false;
        }
        
        return true;
    }
}
