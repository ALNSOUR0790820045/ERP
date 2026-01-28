<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Overtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'project_id', 'overtime_date', 'start_time', 'end_time',
        'hours', 'overtime_type', 'overtime_rate_id', 'rate_multiplier',
        'hourly_rate', 'total_amount', 'reason', 'status',
        'approved_by', 'approved_at', 'created_by',
    ];

    protected $casts = [
        'overtime_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'approved_at' => 'datetime',
        'hours' => 'decimal:2',
        'rate_multiplier' => 'decimal:2',
        'hourly_rate' => 'decimal:3',
        'total_amount' => 'decimal:3',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function overtimeRate(): BelongsTo { return $this->belongsTo(OvertimeRate::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeApproved($query) { return $query->where('status', 'approved'); }
}
