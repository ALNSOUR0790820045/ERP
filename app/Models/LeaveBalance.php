<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'opening_balance',
        'entitlement',
        'used',
        'pending',
        'adjustment',
        'carry_forward',
        'closing_balance',
    ];

    protected $casts = [
        'year' => 'integer',
        'opening_balance' => 'decimal:1',
        'entitlement' => 'decimal:1',
        'used' => 'decimal:1',
        'pending' => 'decimal:1',
        'adjustment' => 'decimal:1',
        'carry_forward' => 'decimal:1',
        'closing_balance' => 'decimal:1',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function getAvailableBalanceAttribute(): float
    {
        return $this->opening_balance + $this->entitlement + $this->carry_forward 
            + $this->adjustment - $this->used - $this->pending;
    }

    public function recalculateClosingBalance(): void
    {
        $this->closing_balance = $this->opening_balance + $this->entitlement 
            + $this->carry_forward + $this->adjustment - $this->used;
        $this->save();
    }

    public static function getOrCreateForEmployee(int $employeeId, int $leaveTypeId, int $year): self
    {
        return static::firstOrCreate(
            [
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'year' => $year,
            ],
            [
                'opening_balance' => 0,
                'entitlement' => 0,
                'used' => 0,
                'pending' => 0,
                'adjustment' => 0,
                'carry_forward' => 0,
                'closing_balance' => 0,
            ]
        );
    }
}
