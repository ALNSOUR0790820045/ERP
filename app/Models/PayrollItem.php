<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_id', 'employee_id', 'working_days', 'absent_days',
        'overtime_hours', 'basic_salary', 'total_allowances', 'overtime_amount',
        'total_earnings', 'total_deductions', 'net_salary', 'status',
    ];

    protected $casts = [
        'overtime_hours' => 'decimal:2',
        'basic_salary' => 'decimal:3',
        'total_allowances' => 'decimal:3',
        'overtime_amount' => 'decimal:3',
        'total_earnings' => 'decimal:3',
        'total_deductions' => 'decimal:3',
        'net_salary' => 'decimal:3',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
