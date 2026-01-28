<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendancePunch extends Model
{
    protected $fillable = [
        'employee_id',
        'punch_date',
        'punch_time',
        'punch_type',
        'device_id',
        'latitude',
        'longitude',
        'ip_address',
        'notes',
    ];

    protected $casts = [
        'punch_date' => 'date',
        'punch_time' => 'datetime:H:i:s',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getPunchTypeNameAttribute(): string
    {
        return match($this->punch_type) {
            'in' => 'دخول',
            'out' => 'خروج',
            'break_start' => 'بداية استراحة',
            'break_end' => 'نهاية استراحة',
            default => $this->punch_type,
        };
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('punch_date', $date);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }
}
