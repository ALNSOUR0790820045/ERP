<?php

namespace App\Models\WMS;

use App\Models\Material;
use App\Models\User;
use App\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanListItem extends Model
{
    protected $table = 'wms_scan_list_items';

    protected $fillable = [
        'scan_list_id', 'line_number', 'material_id', 'expected_barcode', 'scanned_barcode',
        'from_location_id', 'to_location_id', 'expected_quantity', 'scanned_quantity',
        'variance_quantity', 'batch_number', 'serial_number', 'status',
        'scanned_at', 'scanned_by', 'notes',
    ];

    protected $casts = [
        'expected_quantity' => 'decimal:3',
        'scanned_quantity' => 'decimal:3',
        'variance_quantity' => 'decimal:3',
        'scanned_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PARTIAL = 'partial';
    const STATUS_COMPLETED = 'completed';
    const STATUS_SKIPPED = 'skipped';

    public function scanList(): BelongsTo
    {
        return $this->belongsTo(ScanList::class, 'scan_list_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'to_location_id');
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scan(string $barcode, float $quantity, User $user): void
    {
        $this->scanned_quantity += $quantity;
        $this->scanned_barcode = $barcode;
        $this->scanned_by = $user->id;
        $this->scanned_at = now();
        $this->variance_quantity = $this->expected_quantity - $this->scanned_quantity;
        
        if ($this->scanned_quantity >= $this->expected_quantity) {
            $this->status = self::STATUS_COMPLETED;
        } elseif ($this->scanned_quantity > 0) {
            $this->status = self::STATUS_PARTIAL;
        }
        
        $this->save();
        $this->scanList->updateProgress();
    }

    public function skip(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_SKIPPED,
            'notes' => $reason,
        ]);
        $this->scanList->updateProgress();
    }

    public function hasVariance(): bool
    {
        return $this->variance_quantity != 0;
    }
}
