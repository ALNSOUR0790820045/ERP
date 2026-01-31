<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeTaxBracket extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'year',
        'taxpayer_type',
        'from_amount',
        'to_amount',
        'rate',
        'fixed_amount',
        'exemption_amount',
        'is_active',
    ];

    protected $casts = [
        'from_amount' => 'decimal:3',
        'to_amount' => 'decimal:3',
        'rate' => 'decimal:2',
        'fixed_amount' => 'decimal:3',
        'exemption_amount' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    // الثوابت
    public const TAXPAYER_TYPES = [
        'individual' => 'فرد',
        'family' => 'معيل أسرة',
    ];

    /**
     * حساب ضريبة الدخل للراتب
     */
    public static function calculateTax(float $annualIncome, string $taxpayerType = 'individual', ?int $year = null): float
    {
        $year = $year ?? date('Y');
        
        $brackets = self::where('year', $year)
            ->where('taxpayer_type', $taxpayerType)
            ->where('is_active', true)
            ->orderBy('from_amount')
            ->get();

        if ($brackets->isEmpty()) {
            return 0;
        }

        $totalTax = 0;
        $remainingIncome = $annualIncome;

        foreach ($brackets as $bracket) {
            // خصم الإعفاء
            $taxableIncome = $remainingIncome - $bracket->exemption_amount;
            if ($taxableIncome <= 0) {
                break;
            }

            // حساب الشريحة
            $bracketRange = $bracket->to_amount ? ($bracket->to_amount - $bracket->from_amount) : $taxableIncome;
            $taxableInBracket = min($taxableIncome, $bracketRange);
            
            $totalTax += $bracket->fixed_amount + ($taxableInBracket * ($bracket->rate / 100));
            
            $remainingIncome -= $bracketRange;
            if ($remainingIncome <= 0) {
                break;
            }
        }

        return round($totalTax, 3);
    }

    /**
     * حساب الضريبة الشهرية
     */
    public static function calculateMonthlyTax(float $monthlySalary, string $taxpayerType = 'individual'): float
    {
        $annualSalary = $monthlySalary * 12;
        $annualTax = self::calculateTax($annualSalary, $taxpayerType);
        return round($annualTax / 12, 3);
    }
}
