<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceCalendar extends Model
{
    use HasFactory;

    protected $table = 'resource_calendars';

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'calendar_type',
        'is_default',
        'is_global',
        'default_start_time',
        'default_end_time',
        'hours_per_day',
        'hours_per_week',
        'days_per_month',
        'working_days',
        'parent_calendar_id',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_global' => 'boolean',
        'hours_per_day' => 'decimal:2',
        'hours_per_week' => 'decimal:2',
        'days_per_month' => 'decimal:2',
        'working_days' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parentCalendar(): BelongsTo
    {
        return $this->belongsTo(ResourceCalendar::class, 'parent_calendar_id');
    }

    public function childCalendars(): HasMany
    {
        return $this->hasMany(ResourceCalendar::class, 'parent_calendar_id');
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(ResourceCalendarException::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ResourceCalendarAssignment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeStandard($query)
    {
        return $query->where('calendar_type', 'standard');
    }

    public function scopeShift($query)
    {
        return $query->where('calendar_type', 'shift');
    }

    public function scopeForProject($query, int $projectId)
    {
        return $query->where(function ($q) use ($projectId) {
            $q->where('project_id', $projectId)
                ->orWhere('is_global', true);
        });
    }

    // Methods
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function setAsDefault(): void
    {
        // Remove default from other calendars in same project
        if ($this->project_id) {
            self::where('project_id', $this->project_id)
                ->where('id', '!=', $this->id)
                ->update(['is_default' => false]);
        }
        
        $this->update(['is_default' => true]);
    }

    public function getCalendarTypeArabicAttribute(): string
    {
        $types = [
            'standard' => 'قياسي',
            'shift' => 'وردية',
            'custom' => 'مخصص',
            'holiday' => 'إجازات',
        ];

        return $types[$this->calendar_type] ?? $this->calendar_type;
    }

    public function getWorkingDaysListAttribute(): array
    {
        $defaultDays = [1, 2, 3, 4, 5]; // Monday to Friday
        return $this->working_days ?? $defaultDays;
    }

    public function isWorkingDay(int $dayOfWeek): bool
    {
        return in_array($dayOfWeek, $this->working_days_list);
    }

    public function isWorkingDate(\DateTimeInterface $date): bool
    {
        $dayOfWeek = (int) $date->format('N'); // 1 = Monday, 7 = Sunday
        
        // Check if it's a working day
        if (!$this->isWorkingDay($dayOfWeek)) {
            return false;
        }
        
        // Check exceptions
        $exception = $this->exceptions()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
            
        if ($exception) {
            return $exception->exception_type === 'working';
        }
        
        return true;
    }

    public function getWorkingHours(\DateTimeInterface $date): float
    {
        if (!$this->isWorkingDate($date)) {
            return 0;
        }
        
        // Check for modified hours exception
        $exception = $this->exceptions()
            ->where('exception_type', 'modified_hours')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
            
        if ($exception && $exception->working_hours) {
            return $exception->working_hours;
        }
        
        return $this->hours_per_day;
    }

    public function calculateWorkingDays(\DateTimeInterface $startDate, \DateTimeInterface $endDate): int
    {
        $workingDays = 0;
        $current = clone $startDate;
        
        while ($current <= $endDate) {
            if ($this->isWorkingDate($current)) {
                $workingDays++;
            }
            $current->modify('+1 day');
        }
        
        return $workingDays;
    }

    public function addWorkingDays(\DateTimeInterface $startDate, int $days): \DateTime
    {
        $result = clone $startDate;
        $addedDays = 0;
        
        while ($addedDays < $days) {
            $result->modify('+1 day');
            if ($this->isWorkingDate($result)) {
                $addedDays++;
            }
        }
        
        return $result;
    }

    public static function getDefaultForProject(int $projectId): ?self
    {
        return self::forProject($projectId)
            ->active()
            ->default()
            ->first()
            ?? self::global()->active()->default()->first();
    }
}
