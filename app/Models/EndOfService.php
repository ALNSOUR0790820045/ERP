<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndOfService extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'termination_type', 'termination_date', 'last_working_day',
        'years_of_service', 'months_of_service', 'days_of_service',
        'basic_salary', 'total_salary', 'calculation_method',
        'first_5_years_amount', 'after_5_years_amount', 'gross_amount',
        'deductions', 'net_amount', 'payment_date', 'payment_reference',
        'status', 'approved_by', 'approved_at', 'notes',
    ];

    protected $casts = [
        'termination_date' => 'date',
        'last_working_day' => 'date',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'years_of_service' => 'integer',
        'months_of_service' => 'integer',
        'days_of_service' => 'integer',
        'basic_salary' => 'decimal:3',
        'total_salary' => 'decimal:3',
        'first_5_years_amount' => 'decimal:3',
        'after_5_years_amount' => 'decimal:3',
        'gross_amount' => 'decimal:3',
        'deductions' => 'decimal:3',
        'net_amount' => 'decimal:3',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopePaid($query) { return $query->where('status', 'paid'); }
}
