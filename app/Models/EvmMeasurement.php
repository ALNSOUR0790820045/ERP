<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * قياس الأداء EVM
 * EVM Performance Measurement
 */
class EvmMeasurement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'baseline_id',
        'measurement_number',
        'measurement_date',
        'data_date',
        'period_type',
        'planned_value',
        'earned_value',
        'actual_cost',
        'budget_at_completion',
        'physical_progress',
        'planned_progress',
        'schedule_variance',
        'cost_variance',
        'variance_at_completion',
        'schedule_performance_index',
        'cost_performance_index',
        'critical_ratio',
        'to_complete_performance_index',
        'estimate_at_completion',
        'estimate_to_complete',
        'estimated_completion_days',
        'estimated_completion_date',
        'schedule_status',
        'cost_status',
        'overall_status',
        'analysis_notes',
        'corrective_actions',
        'metadata',
        'measured_by',
        'approved_by',
        'status',
    ];

    protected $casts = [
        'measurement_date' => 'date',
        'data_date' => 'date',
        'estimated_completion_date' => 'date',
        'planned_value' => 'decimal:3',
        'earned_value' => 'decimal:3',
        'actual_cost' => 'decimal:3',
        'budget_at_completion' => 'decimal:3',
        'physical_progress' => 'decimal:4',
        'planned_progress' => 'decimal:4',
        'schedule_variance' => 'decimal:3',
        'cost_variance' => 'decimal:3',
        'variance_at_completion' => 'decimal:3',
        'schedule_performance_index' => 'decimal:4',
        'cost_performance_index' => 'decimal:4',
        'critical_ratio' => 'decimal:4',
        'to_complete_performance_index' => 'decimal:4',
        'estimate_at_completion' => 'decimal:3',
        'estimate_to_complete' => 'decimal:3',
        'metadata' => 'array',
    ];

    // Boot method لتوليد رقم القياس تلقائياً
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->measurement_number)) {
                $year = date('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $model->measurement_number = "EVM-{$year}-" . str_pad($count, 5, '0', STR_PAD_LEFT);
            }
        });

        static::saving(function ($model) {
            // حساب المؤشرات تلقائياً
            $model->calculateIndicators();
        });
    }

    // العلاقات
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function baseline(): BelongsTo
    {
        return $this->belongsTo(ProjectBaseline::class, 'baseline_id');
    }

    public function measuredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'measured_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(EvmMeasurementDetail::class, 'measurement_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(EvmAlert::class, 'measurement_id');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    // Methods
    /**
     * حساب مؤشرات EVM
     */
    public function calculateIndicators(): void
    {
        // Schedule Variance (SV) = EV - PV
        $this->schedule_variance = $this->earned_value - $this->planned_value;
        
        // Cost Variance (CV) = EV - AC
        $this->cost_variance = $this->earned_value - $this->actual_cost;
        
        // Schedule Performance Index (SPI) = EV / PV
        $this->schedule_performance_index = $this->planned_value > 0 
            ? $this->earned_value / $this->planned_value 
            : 1;
        
        // Cost Performance Index (CPI) = EV / AC
        $this->cost_performance_index = $this->actual_cost > 0 
            ? $this->earned_value / $this->actual_cost 
            : 1;
        
        // Critical Ratio (CR) = SPI × CPI
        $this->critical_ratio = $this->schedule_performance_index * $this->cost_performance_index;
        
        // Estimate at Completion (EAC) = BAC / CPI
        if ($this->cost_performance_index > 0) {
            $this->estimate_at_completion = $this->budget_at_completion / $this->cost_performance_index;
        }
        
        // Estimate to Complete (ETC) = EAC - AC
        if ($this->estimate_at_completion) {
            $this->estimate_to_complete = $this->estimate_at_completion - $this->actual_cost;
        }
        
        // Variance at Completion (VAC) = BAC - EAC
        if ($this->estimate_at_completion) {
            $this->variance_at_completion = $this->budget_at_completion - $this->estimate_at_completion;
        }
        
        // To-Complete Performance Index (TCPI) = (BAC - EV) / (BAC - AC)
        $denominator = $this->budget_at_completion - $this->actual_cost;
        if ($denominator > 0) {
            $this->to_complete_performance_index = 
                ($this->budget_at_completion - $this->earned_value) / $denominator;
        }
        
        // تحديد حالة الجدول الزمني
        $this->schedule_status = $this->determineScheduleStatus();
        
        // تحديد حالة التكلفة
        $this->cost_status = $this->determineCostStatus();
        
        // تحديد الحالة العامة
        $this->overall_status = $this->determineOverallStatus();
    }

    /**
     * تحديد حالة الجدول الزمني
     */
    protected function determineScheduleStatus(): string
    {
        $spi = $this->schedule_performance_index;
        
        if ($spi >= 1.05) return 'ahead';
        if ($spi >= 0.95) return 'on_track';
        if ($spi >= 0.80) return 'behind';
        return 'critical';
    }

    /**
     * تحديد حالة التكلفة
     */
    protected function determineCostStatus(): string
    {
        $cpi = $this->cost_performance_index;
        
        if ($cpi >= 1.05) return 'under';
        if ($cpi >= 0.95) return 'on_budget';
        if ($cpi >= 0.80) return 'over';
        return 'critical';
    }

    /**
     * تحديد الحالة العامة
     */
    protected function determineOverallStatus(): string
    {
        $spi = $this->schedule_performance_index;
        $cpi = $this->cost_performance_index;
        
        if ($spi >= 0.95 && $cpi >= 0.95) return 'green';
        if ($spi < 0.80 || $cpi < 0.80) return 'red';
        return 'yellow';
    }

    // Accessors
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'submitted' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'gray',
        };
    }

    public function getOverallStatusColorAttribute(): string
    {
        return match($this->overall_status) {
            'green' => 'success',
            'yellow' => 'warning',
            'red' => 'danger',
            default => 'gray',
        };
    }

    public function getSpiColorAttribute(): string
    {
        $spi = $this->schedule_performance_index;
        if ($spi >= 0.95) return 'success';
        if ($spi >= 0.80) return 'warning';
        return 'danger';
    }

    public function getCpiColorAttribute(): string
    {
        $cpi = $this->cost_performance_index;
        if ($cpi >= 0.95) return 'success';
        if ($cpi >= 0.80) return 'warning';
        return 'danger';
    }

    /**
     * الحصول على نسبة الإنجاز كنص
     */
    public function getProgressTextAttribute(): string
    {
        return number_format($this->physical_progress, 2) . '%';
    }
}
