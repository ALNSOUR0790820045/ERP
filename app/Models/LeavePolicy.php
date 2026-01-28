<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeavePolicy extends Model
{
    protected $fillable = [
        'leave_type_id',
        'department_id',
        'job_title_id',
        'annual_entitlement',
        'max_carry_forward',
        'max_accumulation',
        'min_service_months',
        'is_paid',
        'requires_attachment',
        'max_consecutive_days',
        'advance_notice_days',
        'conditions',
        'is_active',
    ];

    protected $casts = [
        'annual_entitlement' => 'decimal:1',
        'max_carry_forward' => 'decimal:1',
        'max_accumulation' => 'decimal:1',
        'min_service_months' => 'integer',
        'is_paid' => 'boolean',
        'requires_attachment' => 'boolean',
        'max_consecutive_days' => 'integer',
        'advance_notice_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEmployee($query, Employee $employee)
    {
        return $query->where(function ($q) use ($employee) {
            $q->whereNull('department_id')
              ->orWhere('department_id', $employee->department_id);
        })->where(function ($q) use ($employee) {
            $q->whereNull('job_title_id')
              ->orWhere('job_title_id', $employee->job_title_id);
        });
    }
}
