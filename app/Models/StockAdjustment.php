<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'adjustment_number',
        'warehouse_id',
        'adjustment_date',
        'adjustment_type',
        'stock_count_id',
        'total_increase_value',
        'total_decrease_value',
        'net_adjustment_value',
        'journal_entry_id',
        'status',
        'prepared_by',
        'approved_by',
        'approval_date',
        'notes',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'total_increase_value' => 'decimal:3',
        'total_decrease_value' => 'decimal:3',
        'net_adjustment_value' => 'decimal:3',
        'approval_date' => 'date',
    ];

    // العلاقات
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // الثوابت
    public const ADJUSTMENT_TYPES = [
        'inventory_count' => 'من الجرد',
        'damage' => 'تلف',
        'expiry' => 'انتهاء صلاحية',
        'correction' => 'تصحيح',
        'initial' => 'أرصدة افتتاحية',
    ];

    public const STATUSES = [
        'draft' => 'مسودة',
        'submitted' => 'مقدم',
        'approved' => 'معتمد',
        'posted' => 'مرحل',
        'cancelled' => 'ملغي',
    ];

    /**
     * حساب الإجماليات
     */
    public function calculateTotals(): void
    {
        $this->total_increase_value = $this->items()
            ->where('adjustment_quantity', '>', 0)
            ->sum('adjustment_value');

        $this->total_decrease_value = abs($this->items()
            ->where('adjustment_quantity', '<', 0)
            ->sum('adjustment_value'));

        $this->net_adjustment_value = $this->total_increase_value - $this->total_decrease_value;
    }
}
