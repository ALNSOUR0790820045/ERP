<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تنبيهات EVM
 * EVM Alerts
 */
class EvmAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'measurement_id',
        'alert_type',
        'severity',
        'title',
        'description',
        'threshold_value',
        'actual_value',
        'recommended_action',
        'is_acknowledged',
        'acknowledged_by',
        'acknowledged_at',
        'is_resolved',
        'resolved_at',
    ];

    protected $casts = [
        'threshold_value' => 'decimal:4',
        'actual_value' => 'decimal:4',
        'is_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    // العلاقات
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function measurement(): BelongsTo
    {
        return $this->belongsTo(EvmMeasurement::class, 'measurement_id');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    // Accessors
    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'info' => 'info',
            'warning' => 'warning',
            'critical' => 'danger',
            default => 'gray',
        };
    }

    public function getAlertTypeLabelsAttribute(): array
    {
        return [
            'spi_low' => 'SPI منخفض',
            'cpi_low' => 'CPI منخفض',
            'behind_schedule' => 'متأخر عن الجدول',
            'over_budget' => 'تجاوز الميزانية',
            'critical_variance' => 'فرق حرج',
            'forecast_overrun' => 'تجاوز متوقع',
            'tcpi_critical' => 'TCPI حرج',
        ];
    }

    public function getAlertTypeLabelAttribute(): string
    {
        return $this->alertTypeLabels[$this->alert_type] ?? $this->alert_type;
    }

    // Methods
    public function acknowledge($userId): bool
    {
        $this->is_acknowledged = true;
        $this->acknowledged_by = $userId;
        $this->acknowledged_at = now();
        return $this->save();
    }

    public function resolve(): bool
    {
        $this->is_resolved = true;
        $this->resolved_at = now();
        return $this->save();
    }

    /**
     * إنشاء تنبيهات من قياس EVM
     */
    public static function createFromMeasurement(EvmMeasurement $measurement): array
    {
        $alerts = [];

        // تنبيه SPI منخفض
        if ($measurement->schedule_performance_index < 0.80) {
            $alerts[] = static::create([
                'project_id' => $measurement->project_id,
                'measurement_id' => $measurement->id,
                'alert_type' => 'spi_low',
                'severity' => $measurement->schedule_performance_index < 0.70 ? 'critical' : 'warning',
                'title' => 'مؤشر أداء الجدول الزمني منخفض',
                'description' => sprintf(
                    'SPI = %.2f أقل من الحد المقبول (0.80). المشروع متأخر عن الجدول الزمني.',
                    $measurement->schedule_performance_index
                ),
                'threshold_value' => 0.80,
                'actual_value' => $measurement->schedule_performance_index,
                'recommended_action' => 'مراجعة الجدول الزمني وتحديد أسباب التأخير واتخاذ إجراءات تصحيحية.',
            ]);
        }

        // تنبيه CPI منخفض
        if ($measurement->cost_performance_index < 0.80) {
            $alerts[] = static::create([
                'project_id' => $measurement->project_id,
                'measurement_id' => $measurement->id,
                'alert_type' => 'cpi_low',
                'severity' => $measurement->cost_performance_index < 0.70 ? 'critical' : 'warning',
                'title' => 'مؤشر أداء التكلفة منخفض',
                'description' => sprintf(
                    'CPI = %.2f أقل من الحد المقبول (0.80). المشروع يتجاوز الميزانية.',
                    $measurement->cost_performance_index
                ),
                'threshold_value' => 0.80,
                'actual_value' => $measurement->cost_performance_index,
                'recommended_action' => 'مراجعة التكاليف وتحديد أسباب التجاوز واتخاذ إجراءات للتحكم في المصاريف.',
            ]);
        }

        // تنبيه تجاوز الميزانية المتوقع
        if ($measurement->variance_at_completion < 0) {
            $overrun = abs($measurement->variance_at_completion);
            $overrunPercentage = ($overrun / $measurement->budget_at_completion) * 100;
            
            $alerts[] = static::create([
                'project_id' => $measurement->project_id,
                'measurement_id' => $measurement->id,
                'alert_type' => 'forecast_overrun',
                'severity' => $overrunPercentage > 20 ? 'critical' : 'warning',
                'title' => 'تجاوز متوقع للميزانية',
                'description' => sprintf(
                    'التكلفة المتوقعة عند الإنجاز (EAC) = %.2f تتجاوز الميزانية بمقدار %.2f (%.1f%%).',
                    $measurement->estimate_at_completion,
                    $overrun,
                    $overrunPercentage
                ),
                'threshold_value' => $measurement->budget_at_completion,
                'actual_value' => $measurement->estimate_at_completion,
                'recommended_action' => 'دراسة خيارات تقليل التكاليف أو طلب ميزانية إضافية.',
            ]);
        }

        return $alerts;
    }
}
