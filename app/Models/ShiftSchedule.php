<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'start_time', 'end_time', 'break_start',
        'break_end', 'break_duration', 'working_hours',
        'is_night_shift', 'overtime_after_hours', 'is_active', 'notes',
    ];

    protected $casts = [
        'break_duration' => 'decimal:2',
        'working_hours' => 'decimal:2',
        'overtime_after_hours' => 'decimal:2',
        'is_night_shift' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function employees(): HasMany { return $this->hasMany(Employee::class); }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeNightShifts($query) { return $query->where('is_night_shift', true); }
}
