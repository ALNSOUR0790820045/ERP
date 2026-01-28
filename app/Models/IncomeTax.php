<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeTax extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'payroll_id', 'tax_year', 'tax_month',
        'gross_salary', 'taxable_income', 'exemptions', 'deductions',
        'tax_bracket_id', 'tax_rate', 'tax_amount',
        'cumulative_income', 'cumulative_tax', 'is_reported', 'notes',
    ];

    protected $casts = [
        'gross_salary' => 'decimal:3',
        'taxable_income' => 'decimal:3',
        'exemptions' => 'decimal:3',
        'deductions' => 'decimal:3',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:3',
        'cumulative_income' => 'decimal:3',
        'cumulative_tax' => 'decimal:3',
        'is_reported' => 'boolean',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function payroll(): BelongsTo { return $this->belongsTo(Payroll::class); }
    public function taxBracket(): BelongsTo { return $this->belongsTo(IncomeTaxBracket::class, 'tax_bracket_id'); }

    public function scopeForYear($query, int $year) { return $query->where('tax_year', $year); }
    public function scopeUnreported($query) { return $query->where('is_reported', false); }
}
