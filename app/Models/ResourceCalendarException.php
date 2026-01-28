<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceCalendarException extends Model
{
    use HasFactory;

    protected $table = 'resource_calendar_exceptions';

    protected $fillable = [
        'resource_calendar_id',
        'name',
        'exception_type',
        'start_date',
        'end_date',
        'is_recurring',
        'recurrence_pattern',
        'recurrence_interval',
        'recurrence_end_date',
        'working_start_time',
        'working_end_time',
        'working_hours',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_recurring' => 'boolean',
        'recurrence_end_date' => 'date',
        'working_hours' => 'decimal:2',
    ];

    // Relationships
    public function calendar(): BelongsTo
    {
        return $this->belongsTo(ResourceCalendar::class, 'resource_calendar_id');
    }

    // Scopes
    public function scopeHolidays($query)
    {
        return $query->where('exception_type', 'holiday');
    }

    public function scopeWorkingExceptions($query)
    {
        return $query->where('exception_type', 'working');
    }

    public function scopeNonWorkingExceptions($query)
    {
        return $query->where('exception_type', 'non_working');
    }

    public function scopeModifiedHours($query)
    {
        return $query->where('exception_type', 'modified_hours');
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function scopeActiveOn($query, $date)
    {
        return $query->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now());
    }

    // Methods
    public function getExceptionTypeArabicAttribute(): string
    {
        $types = [
            'holiday' => 'إجازة',
            'working' => 'يوم عمل',
            'non_working' => 'يوم غير عامل',
            'modified_hours' => 'ساعات معدلة',
        ];

        return $types[$this->exception_type] ?? $this->exception_type;
    }

    public function getRecurrencePatternArabicAttribute(): ?string
    {
        if (!$this->recurrence_pattern) {
            return null;
        }

        $patterns = [
            'daily' => 'يومي',
            'weekly' => 'أسبوعي',
            'monthly' => 'شهري',
            'yearly' => 'سنوي',
        ];

        return $patterns[$this->recurrence_pattern] ?? $this->recurrence_pattern;
    }

    public function getDurationDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function isActiveOn(\DateTimeInterface $date): bool
    {
        if (!$this->is_recurring) {
            return $date >= $this->start_date && $date <= $this->end_date;
        }

        // Handle recurring exceptions
        if ($this->recurrence_end_date && $date > $this->recurrence_end_date) {
            return false;
        }

        switch ($this->recurrence_pattern) {
            case 'daily':
                return true;
                
            case 'weekly':
                $weeksSince = $this->start_date->diffInWeeks($date);
                return $weeksSince % ($this->recurrence_interval ?? 1) === 0
                    && $date->dayOfWeek === $this->start_date->dayOfWeek;
                
            case 'monthly':
                $monthsSince = $this->start_date->diffInMonths($date);
                return $monthsSince % ($this->recurrence_interval ?? 1) === 0
                    && $date->day === $this->start_date->day;
                
            case 'yearly':
                $yearsSince = $this->start_date->diffInYears($date);
                return $yearsSince % ($this->recurrence_interval ?? 1) === 0
                    && $date->month === $this->start_date->month
                    && $date->day === $this->start_date->day;
                    
            default:
                return false;
        }
    }

    public function isNonWorkingException(): bool
    {
        return in_array($this->exception_type, ['holiday', 'non_working']);
    }

    public function getNextOccurrence(\DateTimeInterface $fromDate): ?\DateTime
    {
        if (!$this->is_recurring) {
            if ($this->start_date >= $fromDate) {
                return $this->start_date->toDateTime();
            }
            return null;
        }

        $current = clone $fromDate;
        $maxIterations = 366; // Prevent infinite loop
        
        for ($i = 0; $i < $maxIterations; $i++) {
            if ($this->isActiveOn($current)) {
                return $current;
            }
            $current->modify('+1 day');
            
            if ($this->recurrence_end_date && $current > $this->recurrence_end_date) {
                return null;
            }
        }
        
        return null;
    }
}
