<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * إعدادات نهاية الخدمة
 * End of Service Settings (Jordan Labor Law)
 */
class EndOfServiceSetting extends Model
{
    use HasFactory;

    protected $table = 'end_of_service_settings';

    protected $fillable = [
        'year',
        'name',
        'rate_per_year',
        'max_months',
        'calculation_basis',
        'include_allowances',
        'included_allowances',
        'min_service_months',
        'prorate_partial_years',
        'resignation_rate_1_5_years',
        'resignation_rate_5_10_years',
        'resignation_rate_over_10_years',
        'dismissal_without_cause_full',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'rate_per_year' => 'decimal:4',
        'max_months' => 'decimal:2',
        'include_allowances' => 'boolean',
        'included_allowances' => 'array',
        'prorate_partial_years' => 'boolean',
        'resignation_rate_1_5_years' => 'decimal:4',
        'resignation_rate_5_10_years' => 'decimal:4',
        'resignation_rate_over_10_years' => 'decimal:4',
        'dismissal_without_cause_full' => 'boolean',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function calculations(): HasMany
    {
        return $this->hasMany(EndOfServiceCalculation::class, 'settings_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    // Methods
    /**
     * الحصول على الإعدادات الفعّالة لسنة معينة
     */
    public static function getEffectiveSettings($year = null): ?self
    {
        $year = $year ?? date('Y');
        
        return static::active()
            ->where('year', '<=', $year)
            ->orderBy('year', 'desc')
            ->first();
    }

    /**
     * الحصول على نسبة الاستقالة حسب سنوات الخدمة
     */
    public function getResignationRate(float $serviceYears): float
    {
        if ($serviceYears <= 5) {
            return $this->resignation_rate_1_5_years;
        } elseif ($serviceYears <= 10) {
            return $this->resignation_rate_5_10_years;
        } else {
            return $this->resignation_rate_over_10_years;
        }
    }

    /**
     * الحصول على وصف أساس الحساب
     */
    public function getCalculationBasisLabelAttribute(): string
    {
        return match($this->calculation_basis) {
            'basic_salary' => 'الراتب الأساسي',
            'gross_salary' => 'إجمالي الراتب',
            'last_salary' => 'آخر راتب',
            default => $this->calculation_basis,
        };
    }
}
