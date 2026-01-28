<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_number', 'employee_id', 'contract_type', 'start_date', 'end_date',
        'probation_end_date', 'basic_salary', 'currency_id', 'working_hours_per_day',
        'working_days_per_week', 'annual_leave_days', 'sick_leave_days',
        'notice_period_days', 'terms', 'status',
        'signed_date', 'signed_by_employee', 'signed_by_company',
        'termination_date', 'termination_reason', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'probation_end_date' => 'date',
        'signed_date' => 'date',
        'termination_date' => 'date',
        'basic_salary' => 'decimal:3',
        'working_hours_per_day' => 'decimal:2',
        'signed_by_employee' => 'boolean',
        'signed_by_company' => 'boolean',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function renewals(): HasMany { return $this->hasMany(WorkContractRenewal::class); }

    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeExpiringSoon($query, int $days = 30) {
        return $query->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }
}
