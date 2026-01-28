<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReorderLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'warehouse_id',
        'minimum_quantity',
        'reorder_quantity',
        'maximum_quantity',
        'lead_time_days',
        'safety_stock',
        'economic_order_quantity',
        'auto_reorder',
        'is_active',
    ];

    protected $casts = [
        'minimum_quantity' => 'decimal:3',
        'reorder_quantity' => 'decimal:3',
        'maximum_quantity' => 'decimal:3',
        'safety_stock' => 'decimal:3',
        'economic_order_quantity' => 'decimal:3',
        'auto_reorder' => 'boolean',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * التحقق من الحاجة لإعادة الطلب
     */
    public function needsReorder(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $currentStock = $this->material->getCurrentStock($this->warehouse_id);
        return $currentStock <= $this->minimum_quantity;
    }

    /**
     * حساب الكمية الاقتصادية للطلب (EOQ)
     */
    public static function calculateEOQ(float $annualDemand, float $orderCost, float $holdingCost): float
    {
        if ($holdingCost <= 0) {
            return 0;
        }
        
        return sqrt((2 * $annualDemand * $orderCost) / $holdingCost);
    }

    /**
     * حساب مخزون الأمان
     */
    public static function calculateSafetyStock(float $maxDailyUsage, float $avgDailyUsage, float $maxLeadTime, float $avgLeadTime): float
    {
        return ($maxDailyUsage * $maxLeadTime) - ($avgDailyUsage * $avgLeadTime);
    }
}
