<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialSecurityReport extends Model
{
    use HasFactory;

    protected $table = 'social_security_contributions';

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
        'year' => 'integer',
        'month' => 'integer',
        'basic_salary' => 'decimal:2',
        'contributable_salary' => 'decimal:2',
        'employer_contribution' => 'decimal:2',
        'employee_contribution' => 'decimal:2',
        'total_contribution' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // العلاقات
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    public function scopeForPeriod($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    // Accessors
    public function getPeriodNameAttribute()
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
        ];
        return $months[$this->month] . ' ' . $this->year;
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'قيد الانتظار',
            'paid' => 'مدفوع',
            'cancelled' => 'ملغي',
            default => $this->status,
        };
    }

    // Methods
    public function markAsPaid($referenceNumber = null)
    {
        $this->update([
            'status' => 'paid',
            'payment_date' => now(),
            'reference_number' => $referenceNumber,
        ]);
    }

    // Static Methods
    public static function getMonthlyReport($year, $month)
    {
        return static::forPeriod($year, $month)
            ->with('employee')
            ->get()
            ->groupBy('status');
    }

    public static function getTotalsByPeriod($year, $month)
    {
        return static::forPeriod($year, $month)
            ->selectRaw('
                SUM(basic_salary) as total_basic_salary,
                SUM(contributable_salary) as total_contributable_salary,
                SUM(employer_contribution) as total_employer_contribution,
                SUM(employee_contribution) as total_employee_contribution,
                SUM(total_contribution) as grand_total
            ')
            ->first();
    }
}
