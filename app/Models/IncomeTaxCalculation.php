<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeTaxCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'payroll_id',
        'year',
        'month',
        'gross_salary',
        'social_security_deduction',
        'taxable_income',
        'monthly_exemption',
        'net_taxable_income',
        'tax_amount',
        'cumulative_income',
        'cumulative_tax',
    ];

    protected $casts = [
        'gross_salary' => 'decimal:3',
        'social_security_deduction' => 'decimal:3',
        'taxable_income' => 'decimal:3',
        'monthly_exemption' => 'decimal:3',
        'net_taxable_income' => 'decimal:3',
        'tax_amount' => 'decimal:3',
        'cumulative_income' => 'decimal:3',
        'cumulative_tax' => 'decimal:3',
    ];

    // العلاقات
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
