<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * حساب نهاية الخدمة
 * End of Service Calculation (Jordan Labor Law)
 */
class EndOfServiceCalculation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'settings_id',
        'calculation_number',
        'hire_date',
        'termination_date',
        'service_years',
        'service_months',
        'service_days',
        'total_service_years',
        'termination_type',
        'basic_salary',
        'total_allowances',
        'calculation_salary',
        'rate_applied',
        'gross_entitlement',
        'loan_deductions',
        'advance_deductions',
        'other_deductions',
        'deduction_notes',
        'net_entitlement',
        'status',
        'approved_by',
        'approved_at',
        'payment_date',
        'payment_reference',
        'payment_voucher_id',
        'notes',
        'calculation_breakdown',
        'calculated_by',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
        'total_service_years' => 'decimal:4',
        'basic_salary' => 'decimal:3',
        'total_allowances' => 'decimal:3',
        'calculation_salary' => 'decimal:3',
        'rate_applied' => 'decimal:4',
        'gross_entitlement' => 'decimal:3',
        'loan_deductions' => 'decimal:3',
        'advance_deductions' => 'decimal:3',
        'other_deductions' => 'decimal:3',
        'net_entitlement' => 'decimal:3',
        'approved_at' => 'datetime',
        'payment_date' => 'date',
        'calculation_breakdown' => 'array',
    ];

    // أنواع إنهاء الخدمة
    const TERMINATION_TYPES = [
        'resignation' => 'استقالة',
        'dismissal_with_cause' => 'فصل مع سبب',
        'dismissal_without_cause' => 'فصل بدون سبب',
        'contract_end' => 'انتهاء العقد',
        'retirement' => 'تقاعد',
        'death' => 'وفاة',
        'disability' => 'عجز',
        'company_closure' => 'إغلاق الشركة',
    ];

    // Boot method
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->calculation_number)) {
                $year = date('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $model->calculation_number = "EOS-{$year}-" . str_pad($count, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // العلاقات
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function settings(): BelongsTo
    {
        return $this->belongsTo(EndOfServiceSetting::class, 'settings_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public function provisions(): HasMany
    {
        return $this->hasMany(EndOfServiceProvision::class, 'employee_id', 'employee_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EndOfServiceLog::class, 'calculation_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    // Accessors
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'pending_approval' => 'warning',
            'approved' => 'info',
            'paid' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'مسودة',
            'pending_approval' => 'بانتظار الموافقة',
            'approved' => 'معتمد',
            'paid' => 'مدفوع',
            'cancelled' => 'ملغى',
            default => $this->status,
        };
    }

    public function getTerminationTypeLabelAttribute(): string
    {
        return self::TERMINATION_TYPES[$this->termination_type] ?? $this->termination_type;
    }

    public function getServicePeriodTextAttribute(): string
    {
        $parts = [];
        if ($this->service_years > 0) {
            $parts[] = $this->service_years . ' سنة';
        }
        if ($this->service_months > 0) {
            $parts[] = $this->service_months . ' شهر';
        }
        if ($this->service_days > 0) {
            $parts[] = $this->service_days . ' يوم';
        }
        return implode(' و ', $parts) ?: '0';
    }

    public function getTotalDeductionsAttribute(): float
    {
        return $this->loan_deductions + $this->advance_deductions + $this->other_deductions;
    }

    // Methods
    /**
     * حساب مكافأة نهاية الخدمة
     */
    public function calculate(): self
    {
        // حساب مدة الخدمة
        $this->calculateServicePeriod();
        
        // الحصول على الإعدادات
        $settings = $this->settings ?? EndOfServiceSetting::getEffectiveSettings(
            $this->termination_date->year
        );
        
        if ($settings) {
            $this->settings_id = $settings->id;
        }
        
        // تحديد الراتب المستخدم في الحساب
        $this->determineCalculationSalary($settings);
        
        // تحديد النسبة المطبقة
        $this->rate_applied = $this->determineRate($settings);
        
        // حساب الاستحقاق الإجمالي
        $this->gross_entitlement = $this->calculateGrossEntitlement($settings);
        
        // حساب صافي الاستحقاق
        $this->net_entitlement = $this->gross_entitlement - $this->totalDeductions;
        
        // إنشاء تفاصيل الحساب
        $this->calculation_breakdown = $this->createBreakdown($settings);
        
        return $this;
    }

    /**
     * حساب مدة الخدمة
     */
    protected function calculateServicePeriod(): void
    {
        $hireDate = $this->hire_date;
        $terminationDate = $this->termination_date;
        
        $diff = $hireDate->diff($terminationDate);
        
        $this->service_years = $diff->y;
        $this->service_months = $diff->m;
        $this->service_days = $diff->d;
        
        // حساب إجمالي السنوات بالكسور
        $totalDays = $hireDate->diffInDays($terminationDate);
        $this->total_service_years = $totalDays / 365.25;
    }

    /**
     * تحديد الراتب المستخدم في الحساب
     */
    protected function determineCalculationSalary(?EndOfServiceSetting $settings): void
    {
        $basis = $settings->calculation_basis ?? 'basic_salary';
        
        switch ($basis) {
            case 'gross_salary':
                $this->calculation_salary = $this->basic_salary + $this->total_allowances;
                break;
            case 'last_salary':
                $this->calculation_salary = $this->basic_salary + $this->total_allowances;
                break;
            default:
                $this->calculation_salary = $this->basic_salary;
        }
    }

    /**
     * تحديد النسبة المطبقة حسب نوع إنهاء الخدمة
     */
    protected function determineRate(?EndOfServiceSetting $settings): float
    {
        if (!$settings) {
            return 1;
        }

        switch ($this->termination_type) {
            case 'resignation':
                return $settings->getResignationRate($this->total_service_years);
            
            case 'dismissal_with_cause':
                return 0; // لا استحقاق في حالة الفصل مع سبب
            
            case 'dismissal_without_cause':
            case 'contract_end':
            case 'retirement':
            case 'death':
            case 'disability':
            case 'company_closure':
                return 1; // استحقاق كامل
            
            default:
                return 1;
        }
    }

    /**
     * حساب الاستحقاق الإجمالي
     */
    protected function calculateGrossEntitlement(?EndOfServiceSetting $settings): float
    {
        if ($this->rate_applied == 0) {
            return 0;
        }

        $ratePerYear = $settings->rate_per_year ?? 1;
        
        // الاستحقاق = الراتب × سنوات الخدمة × المعدل × النسبة المطبقة
        $entitlement = $this->calculation_salary * $this->total_service_years * $ratePerYear * $this->rate_applied;
        
        // التحقق من الحد الأقصى
        if ($settings && $settings->max_months) {
            $maxAmount = $this->calculation_salary * $settings->max_months;
            $entitlement = min($entitlement, $maxAmount);
        }
        
        return round($entitlement, 3);
    }

    /**
     * إنشاء تفاصيل الحساب
     */
    protected function createBreakdown(?EndOfServiceSetting $settings): array
    {
        return [
            'hire_date' => $this->hire_date->toDateString(),
            'termination_date' => $this->termination_date->toDateString(),
            'service_years' => $this->service_years,
            'service_months' => $this->service_months,
            'service_days' => $this->service_days,
            'total_service_years' => $this->total_service_years,
            'termination_type' => $this->termination_type,
            'basic_salary' => $this->basic_salary,
            'total_allowances' => $this->total_allowances,
            'calculation_basis' => $settings->calculation_basis ?? 'basic_salary',
            'calculation_salary' => $this->calculation_salary,
            'rate_per_year' => $settings->rate_per_year ?? 1,
            'rate_applied' => $this->rate_applied,
            'gross_entitlement' => $this->gross_entitlement,
            'loan_deductions' => $this->loan_deductions,
            'advance_deductions' => $this->advance_deductions,
            'other_deductions' => $this->other_deductions,
            'total_deductions' => $this->totalDeductions,
            'net_entitlement' => $this->net_entitlement,
            'calculated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * الموافقة على الحساب
     */
    public function approve($userId): bool
    {
        $this->status = 'approved';
        $this->approved_by = $userId;
        $this->approved_at = now();
        
        $saved = $this->save();
        
        if ($saved) {
            EndOfServiceLog::create([
                'calculation_id' => $this->id,
                'action' => 'approved',
                'new_values' => ['status' => 'approved'],
                'performed_by' => $userId,
            ]);
        }
        
        return $saved;
    }

    /**
     * تسجيل الدفع
     */
    public function markAsPaid($paymentDate, $paymentReference = null, $voucherId = null): bool
    {
        $this->status = 'paid';
        $this->payment_date = $paymentDate;
        $this->payment_reference = $paymentReference;
        $this->payment_voucher_id = $voucherId;
        
        $saved = $this->save();
        
        if ($saved) {
            EndOfServiceLog::create([
                'calculation_id' => $this->id,
                'action' => 'paid',
                'new_values' => [
                    'status' => 'paid',
                    'payment_date' => $paymentDate,
                    'payment_reference' => $paymentReference,
                ],
                'performed_by' => auth()->id(),
            ]);
        }
        
        return $saved;
    }
}
