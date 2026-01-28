<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialSecurityContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'payroll_id',
        'social_security_number',
        'year',
        'month',
        'basic_salary',
        'contributable_salary',
        'employer_contribution',
        'employee_contribution',
        'total_contribution',
        'status',
        'payment_date',
        'reference_number',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:3',
        'contributable_salary' => 'decimal:3',
        'employer_contribution' => 'decimal:3',
        'employee_contribution' => 'decimal:3',
        'total_contribution' => 'decimal:3',
        'payment_date' => 'date',
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

    // الثوابت
    public const STATUSES = [
        'pending' => 'معلق',
        'submitted' => 'مرسل',
        'paid' => 'مدفوع',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
