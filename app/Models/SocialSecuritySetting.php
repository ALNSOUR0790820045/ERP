<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialSecuritySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'employer_rate',
        'employee_rate',
        'total_rate',
        'minimum_wage',
        'maximum_contributable_salary',
        'effective_from',
        'effective_to',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'employer_rate' => 'decimal:2',
        'employee_rate' => 'decimal:2',
        'total_rate' => 'decimal:2',
        'minimum_wage' => 'decimal:3',
        'maximum_contributable_salary' => 'decimal:3',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function contributions(): HasMany
    {
        return $this->hasMany(SocialSecurityContribution::class);
    }

    /**
     * حساب اشتراك الضمان الاجتماعي
     */
    public static function calculateContribution(float $salary): array
    {
        $setting = self::where('is_active', true)
            ->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now());
            })
            ->first();

        if (!$setting) {
            return [
                'employer' => 0,
                'employee' => 0,
                'total' => 0,
            ];
        }

        $contributableSalary = $salary;
        
        // الحد الأعلى للاشتراك
        if ($setting->maximum_contributable_salary && $salary > $setting->maximum_contributable_salary) {
            $contributableSalary = $setting->maximum_contributable_salary;
        }

        $employerContribution = round($contributableSalary * ($setting->employer_rate / 100), 3);
        $employeeContribution = round($contributableSalary * ($setting->employee_rate / 100), 3);

        return [
            'contributable_salary' => $contributableSalary,
            'employer' => $employerContribution,
            'employee' => $employeeContribution,
            'total' => $employerContribution + $employeeContribution,
        ];
    }
}
