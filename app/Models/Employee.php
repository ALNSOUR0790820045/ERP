<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'department_id', 'job_title_id', 'user_id',
        'employee_code', 'first_name_ar', 'last_name_ar', 'first_name_en', 'last_name_en',
        'national_id', 'birth_date', 'gender', 'marital_status', 'nationality',
        'phone', 'mobile', 'email', 'address', 'hire_date', 'employment_type',
        'employment_status', 'termination_date', 'termination_reason', 'basic_salary',
        'currency_id', 'bank_name', 'bank_account', 'iban', 'direct_manager_id',
        'project_id', 'photo', 'is_active',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'basic_salary' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function getFullNameArAttribute(): string
    {
        return $this->first_name_ar . ' ' . $this->last_name_ar;
    }

    public function getFullNameEnAttribute(): string
    {
        return ($this->first_name_en ?? '') . ' ' . ($this->last_name_en ?? '');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function directManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'direct_manager_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(EmploymentContract::class);
    }

    public function salaryComponents(): HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
