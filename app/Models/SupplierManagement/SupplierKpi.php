<?php

namespace App\Models\SupplierManagement;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierKpi extends Model
{
    protected $table = 'supplier_kpis';

    protected $fillable = [
        'supplier_id', 'period_type', 'period_start', 'period_end', 'calculated_by', 'calculated_at',
        'total_orders', 'total_order_value', 'delivered_orders', 'on_time_deliveries',
        'on_time_delivery_rate', 'quality_accepted', 'quality_rejected', 'quality_rate',
        'defect_count', 'defect_rate', 'average_lead_time', 'price_variance', 'response_time_avg',
        'overall_score', 'rating', 'status', 'comments', 'benchmarks', 'trends', 'metadata',
    ];

    protected $casts = [
        'period_start' => 'date', 'period_end' => 'date', 'calculated_at' => 'datetime',
        'total_order_value' => 'decimal:2', 'on_time_delivery_rate' => 'decimal:2',
        'quality_rate' => 'decimal:2', 'defect_rate' => 'decimal:2',
        'average_lead_time' => 'decimal:2', 'price_variance' => 'decimal:2',
        'response_time_avg' => 'decimal:2', 'overall_score' => 'decimal:2',
        'benchmarks' => 'array', 'trends' => 'array', 'metadata' => 'array',
    ];

    const PERIOD_WEEKLY = 'weekly';
    const PERIOD_MONTHLY = 'monthly';
    const PERIOD_QUARTERLY = 'quarterly';
    const PERIOD_YEARLY = 'yearly';

    const RATING_EXCELLENT = 'excellent';
    const RATING_GOOD = 'good';
    const RATING_SATISFACTORY = 'satisfactory';
    const RATING_POOR = 'poor';
    const RATING_CRITICAL = 'critical';

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function calculator(): BelongsTo { return $this->belongsTo(User::class, 'calculated_by'); }

    public function calculateMetrics(): void {
        $this->on_time_delivery_rate = $this->delivered_orders > 0 
            ? ($this->on_time_deliveries / $this->delivered_orders) * 100 : 0;
        
        $qualityTotal = $this->quality_accepted + $this->quality_rejected;
        $this->quality_rate = $qualityTotal > 0 ? ($this->quality_accepted / $qualityTotal) * 100 : 100;
        
        $this->defect_rate = $this->total_orders > 0 ? ($this->defect_count / $this->total_orders) * 100 : 0;
        
        $this->overall_score = ($this->on_time_delivery_rate * 0.4) + 
                               ($this->quality_rate * 0.4) + 
                               ((100 - $this->defect_rate) * 0.2);
        
        $this->rating = $this->determineRating($this->overall_score);
    }

    public function determineRating(float $score): string {
        if ($score >= 95) return self::RATING_EXCELLENT;
        if ($score >= 85) return self::RATING_GOOD;
        if ($score >= 70) return self::RATING_SATISFACTORY;
        if ($score >= 50) return self::RATING_POOR;
        return self::RATING_CRITICAL;
    }

    public static function calculateForPeriod(Supplier $supplier, string $periodType, $start, $end): self {
        $kpi = new self([
            'supplier_id' => $supplier->id, 'period_type' => $periodType,
            'period_start' => $start, 'period_end' => $end,
        ]);
        // Aggregate from PurchaseOrders, SupplierInvoices, etc.
        $kpi->calculateMetrics();
        return $kpi;
    }
}
