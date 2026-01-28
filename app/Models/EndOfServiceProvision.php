<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * مخصصات نهاية الخدمة
 * End of Service Provisions
 */
class EndOfServiceProvision extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'year',
        'month',
        'opening_balance',
        'monthly_provision',
        'adjustment',
        'closing_balance',
        'salary_at_date',
        'service_years_at_date',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:3',
        'monthly_provision' => 'decimal:3',
        'adjustment' => 'decimal:3',
        'closing_balance' => 'decimal:3',
        'salary_at_date' => 'decimal:3',
        'service_years_at_date' => 'decimal:4',
    ];

    // العلاقات
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // Scopes
    public function scopeForPeriod($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // Methods
    /**
     * حساب المخصص الشهري لموظف
     */
    public static function calculateMonthlyProvision(
        Employee $employee,
        int $year,
        int $month,
        ?EndOfServiceSetting $settings = null
    ): self {
        $settings = $settings ?? EndOfServiceSetting::getEffectiveSettings($year);
        
        // الحصول على آخر رصيد
        $lastProvision = static::forEmployee($employee->id)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->first();
        
        $openingBalance = $lastProvision ? $lastProvision->closing_balance : 0;
        
        // حساب سنوات الخدمة
        $hireDate = $employee->hire_date;
        $currentDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();
        $serviceYears = $hireDate->diffInYears($currentDate);
        $serviceYearsDecimal = $hireDate->diffInDays($currentDate) / 365.25;
        
        // حساب الراتب
        $salary = $employee->basic_salary ?? $employee->salary ?? 0;
        
        // حساب المخصص الشهري
        // المخصص الشهري = الراتب × (1/12) × معدل السنة
        $ratePerYear = $settings ? $settings->rate_per_year : 1;
        $monthlyProvision = $salary * (1/12) * $ratePerYear;
        
        // إنشاء السجل
        return static::create([
            'employee_id' => $employee->id,
            'year' => $year,
            'month' => $month,
            'opening_balance' => $openingBalance,
            'monthly_provision' => $monthlyProvision,
            'adjustment' => 0,
            'closing_balance' => $openingBalance + $monthlyProvision,
            'salary_at_date' => $salary,
            'service_years_at_date' => $serviceYearsDecimal,
        ]);
    }

    /**
     * حساب المخصصات لجميع الموظفين لفترة معينة
     */
    public static function calculateForAllEmployees(int $year, int $month): array
    {
        $employees = Employee::active()->get();
        $provisions = [];
        $settings = EndOfServiceSetting::getEffectiveSettings($year);
        
        foreach ($employees as $employee) {
            // التحقق من عدم وجود مخصص مسبق
            $exists = static::forEmployee($employee->id)
                ->forPeriod($year, $month)
                ->exists();
            
            if (!$exists) {
                $provisions[] = static::calculateMonthlyProvision($employee, $year, $month, $settings);
            }
        }
        
        return $provisions;
    }

    /**
     * الحصول على ملخص المخصصات
     */
    public static function getSummary(int $year, ?int $month = null): array
    {
        $query = static::where('year', $year);
        
        if ($month) {
            $query->where('month', $month);
        }
        
        return [
            'total_opening' => $query->sum('opening_balance'),
            'total_monthly' => $query->sum('monthly_provision'),
            'total_adjustments' => $query->sum('adjustment'),
            'total_closing' => $query->sum('closing_balance'),
            'employees_count' => $query->distinct('employee_id')->count(),
        ];
    }
}
