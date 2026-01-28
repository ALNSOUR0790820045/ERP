<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCount extends Model
{
    use HasFactory;

    protected $fillable = [
        'count_number',
        'warehouse_id',
        'count_date',
        'count_type',
        'fiscal_period_id',
        'status',
        'counted_by',
        'verified_by',
        'approved_by',
        'approval_date',
        'notes',
    ];

    protected $casts = [
        'count_date' => 'date',
        'approval_date' => 'date',
    ];

    // العلاقات
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockCountItem::class);
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // الثوابت
    public const COUNT_TYPES = [
        'annual' => 'جرد سنوي',
        'periodic' => 'جرد دوري',
        'spot' => 'جرد فجائي',
        'cycle' => 'جرد دوري ABC',
    ];

    public const STATUSES = [
        'draft' => 'مسودة',
        'in_progress' => 'قيد التنفيذ',
        'counted' => 'تم العد',
        'verified' => 'تم التحقق',
        'approved' => 'معتمد',
        'adjusted' => 'تمت التسوية',
    ];

    public function getCountTypeLabelAttribute(): string
    {
        return self::COUNT_TYPES[$this->count_type] ?? $this->count_type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * حساب الفروقات
     */
    public function calculateVariances(): array
    {
        $totalVarianceQty = 0;
        $totalVarianceValue = 0;
        $itemsWithVariance = 0;

        foreach ($this->items as $item) {
            if ($item->variance_quantity != 0) {
                $totalVarianceQty += abs($item->variance_quantity);
                $totalVarianceValue += abs($item->variance_value);
                $itemsWithVariance++;
            }
        }

        return [
            'total_items' => $this->items->count(),
            'items_with_variance' => $itemsWithVariance,
            'total_variance_quantity' => $totalVarianceQty,
            'total_variance_value' => $totalVarianceValue,
        ];
    }
}
