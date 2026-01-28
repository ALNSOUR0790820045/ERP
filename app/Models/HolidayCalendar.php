<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HolidayCalendar extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'holiday_date', 'holiday_type', 'is_recurring',
        'applies_to', 'year', 'is_paid', 'notes',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'is_recurring' => 'boolean',
        'is_paid' => 'boolean',
        'applies_to' => 'array',
    ];

    public function scopeForYear($query, int $year) { return $query->where('year', $year); }
    public function scopePaid($query) { return $query->where('is_paid', true); }
    public function scopeRecurring($query) { return $query->where('is_recurring', true); }
}
