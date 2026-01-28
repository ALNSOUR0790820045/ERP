<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCountItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_count_id',
        'material_id',
        'batch_number',
        'location',
        'system_quantity',
        'counted_quantity',
        'variance_quantity',
        'unit_cost',
        'system_value',
        'counted_value',
        'variance_value',
        'variance_reason',
        'counted_by',
        'count_time',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:3',
        'counted_quantity' => 'decimal:3',
        'variance_quantity' => 'decimal:3',
        'unit_cost' => 'decimal:3',
        'system_value' => 'decimal:3',
        'counted_value' => 'decimal:3',
        'variance_value' => 'decimal:3',
        'count_time' => 'datetime',
    ];

    // العلاقات
    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    // الثوابت
    public const VARIANCE_REASONS = [
        'counting_error' => 'خطأ في العد',
        'theft' => 'سرقة',
        'damage' => 'تلف',
        'evaporation' => 'تبخر/تطاير',
        'measurement_error' => 'خطأ في القياس',
        'unrecorded_issue' => 'صرف غير مسجل',
        'unrecorded_receipt' => 'استلام غير مسجل',
        'other' => 'أخرى',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // حساب الفروقات
            $item->variance_quantity = $item->counted_quantity - $item->system_quantity;
            $item->system_value = $item->system_quantity * $item->unit_cost;
            $item->counted_value = $item->counted_quantity * $item->unit_cost;
            $item->variance_value = $item->variance_quantity * $item->unit_cost;
        });
    }
}
