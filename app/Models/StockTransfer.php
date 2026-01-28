<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'transfer_date',
        'expected_arrival_date',
        'actual_arrival_date',
        'project_id',
        'total_items',
        'total_value',
        'status',
        'requested_by',
        'approved_by',
        'shipped_by',
        'received_by',
        'notes',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'expected_arrival_date' => 'date',
        'actual_arrival_date' => 'date',
        'total_value' => 'decimal:3',
    ];

    // العلاقات
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function shippedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // الثوابت
    public const STATUSES = [
        'draft' => 'مسودة',
        'pending_approval' => 'بانتظار الموافقة',
        'approved' => 'معتمد',
        'in_transit' => 'قيد النقل',
        'partially_received' => 'استلام جزئي',
        'received' => 'مستلم',
        'cancelled' => 'ملغي',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * حساب الإجماليات
     */
    public function calculateTotals(): void
    {
        $this->total_items = $this->items()->count();
        $this->total_value = $this->items()->sum('total_value');
    }
}
