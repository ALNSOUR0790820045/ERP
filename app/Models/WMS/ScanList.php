<?php

namespace App\Models\WMS;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ScanList extends Model
{
    protected $table = 'wms_scan_lists';

    protected $fillable = [
        'list_code', 'name', 'list_type', 'reference_type', 'reference_id',
        'warehouse_id', 'assigned_to', 'device_id', 'status',
        'total_items', 'scanned_items', 'started_at', 'completed_at',
        'notes', 'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const TYPE_PICK_LIST = 'pick_list';
    const TYPE_RECEIVE_LIST = 'receive_list';
    const TYPE_COUNT_LIST = 'count_list';
    const TYPE_VERIFICATION_LIST = 'verification_list';
    const TYPE_CUSTOM = 'custom';

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(RfDevice::class, 'device_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function items(): HasMany
    {
        return $this->hasMany(ScanListItem::class, 'scan_list_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    public function updateProgress(): void
    {
        $scanned = $this->items()->where('status', 'completed')->count();
        $this->update(['scanned_items' => $scanned]);
        
        if ($scanned >= $this->total_items) {
            $this->complete();
        }
    }

    public function getProgressPercentage(): float
    {
        if ($this->total_items === 0) return 0;
        return round(($this->scanned_items / $this->total_items) * 100, 2);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->list_code)) {
                $prefix = match($model->list_type) {
                    self::TYPE_PICK_LIST => 'PL',
                    self::TYPE_RECEIVE_LIST => 'RL',
                    self::TYPE_COUNT_LIST => 'CL',
                    default => 'SL',
                };
                $model->list_code = $prefix . '-' . date('Ymd') . '-' . str_pad(
                    static::whereDate('created_at', today())->count() + 1,
                    4, '0', STR_PAD_LEFT
                );
            }
        });
    }
}
