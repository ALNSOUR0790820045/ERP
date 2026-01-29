<?php

namespace App\Models\WMS;

use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ScanLog extends Model
{
    protected $table = 'wms_scan_logs';

    protected $fillable = [
        'session_id', 'device_id', 'user_id', 'warehouse_id', 'barcode_scanned',
        'scan_type', 'operation', 'scannable_type', 'scannable_id',
        'result', 'error_message', 'location_id', 'quantity', 'scan_data',
        'latitude', 'longitude', 'scanned_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'scan_data' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'scanned_at' => 'datetime',
    ];

    const TYPE_PRODUCT = 'product';
    const TYPE_LOCATION = 'location';
    const TYPE_PALLET = 'pallet';
    const TYPE_CONTAINER = 'container';
    const TYPE_DOCUMENT = 'document';
    const TYPE_EMPLOYEE = 'employee';
    const TYPE_UNKNOWN = 'unknown';

    const OP_RECEIVE = 'receive';
    const OP_PUTAWAY = 'putaway';
    const OP_PICK = 'pick';
    const OP_PACK = 'pack';
    const OP_SHIP = 'ship';
    const OP_TRANSFER = 'transfer';
    const OP_COUNT = 'count';
    const OP_ADJUST = 'adjust';
    const OP_INQUIRY = 'inquiry';
    const OP_VERIFY = 'verify';

    const RESULT_SUCCESS = 'success';
    const RESULT_NOT_FOUND = 'not_found';
    const RESULT_INVALID = 'invalid';
    const RESULT_DUPLICATE = 'duplicate';
    const RESULT_ERROR = 'error';

    public function session(): BelongsTo
    {
        return $this->belongsTo(RfSession::class, 'session_id');
    }

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

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id');
    }

    public function scannable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeSuccessful($query)
    {
        return $query->where('result', self::RESULT_SUCCESS);
    }

    public function scopeFailed($query)
    {
        return $query->where('result', '!=', self::RESULT_SUCCESS);
    }

    public function scopeForOperation($query, string $operation)
    {
        return $query->where('operation', $operation);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scanned_at', today());
    }

    public function isSuccessful(): bool
    {
        return $this->result === self::RESULT_SUCCESS;
    }

    public static function record(array $data): self
    {
        $data['scanned_at'] = $data['scanned_at'] ?? now();
        
        $log = static::create($data);
        
        // Update session stats if exists
        if ($log->session) {
            $log->session->recordScan($log->isSuccessful());
        }
        
        return $log;
    }
}
