<?php

namespace App\Services\ProjectManagement;

use App\Models\ResourceCalendar;
use App\Models\ResourceCalendarException;
use App\Models\ResourceCalendarAssignment;
use App\Models\Resource;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ResourceCalendarService
{
    /**
     * Create a new resource calendar
     */
    public function createCalendar(int $projectId, array $data): ResourceCalendar
    {
        return ResourceCalendar::create([
            'project_id' => $projectId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'calendar_type' => $data['calendar_type'] ?? 'standard',
            'time_zone' => $data['time_zone'] ?? 'Asia/Amman',
            'work_week' => $data['work_week'] ?? [
                'sunday' => true,
                'monday' => true,
                'tuesday' => true,
                'wednesday' => true,
                'thursday' => true,
                'friday' => false,
                'saturday' => false,
            ],
            'work_hours' => $data['work_hours'] ?? [
                'start' => '08:00',
                'end' => '16:00',
                'break_start' => '12:00',
                'break_end' => '13:00',
            ],
            'hours_per_day' => $data['hours_per_day'] ?? 7,
            'hours_per_week' => $data['hours_per_week'] ?? 35,
            'is_default' => $data['is_default'] ?? false,
            'is_active' => true,
        ]);
    }

    /**
     * Create standard Jordan calendar
     */
    public function createJordanStandardCalendar(int $projectId): ResourceCalendar
    {
        $calendar = $this->createCalendar($projectId, [
            'name' => 'تقويم العمل الأردني القياسي',
            'description' => 'تقويم العمل الرسمي في الأردن - الأحد إلى الخميس',
            'calendar_type' => 'standard',
            'time_zone' => 'Asia/Amman',
            'work_week' => [
                'sunday' => true,
                'monday' => true,
                'tuesday' => true,
                'wednesday' => true,
                'thursday' => true,
                'friday' => false,
                'saturday' => false,
            ],
            'work_hours' => [
                'start' => '08:00',
                'end' => '16:00',
                'break_start' => '12:00',
                'break_end' => '13:00',
            ],
            'hours_per_day' => 7,
            'hours_per_week' => 35,
            'is_default' => true,
        ]);

        // Add Jordanian public holidays
        $this->addJordanianHolidays($calendar->id, now()->year);

        return $calendar;
    }

    /**
     * Add Jordanian public holidays
     */
    public function addJordanianHolidays(int $calendarId, int $year): void
    {
        $holidays = [
            ['name' => 'رأس السنة الميلادية', 'date' => "{$year}-01-01"],
            ['name' => 'عيد العمال', 'date' => "{$year}-05-01"],
            ['name' => 'عيد الاستقلال', 'date' => "{$year}-05-25"],
            ['name' => 'عيد الجلوس', 'date' => "{$year}-06-09"],
            ['name' => 'عيد ميلاد الملك', 'date' => "{$year}-01-30"],
            ['name' => 'عيد الجيش والثورة العربية الكبرى', 'date' => "{$year}-06-10"],
            ['name' => 'عيد الميلاد المجيد', 'date' => "{$year}-12-25"],
        ];

        foreach ($holidays as $holiday) {
            $this->addException($calendarId, [
                'name' => $holiday['name'],
                'exception_type' => 'holiday',
                'start_date' => $holiday['date'],
                'end_date' => $holiday['date'],
                'is_working' => false,
            ]);
        }
    }

    /**
     * Add calendar exception (holiday, vacation, special working day)
     */
    public function addException(int $calendarId, array $data): ResourceCalendarException
    {
        return ResourceCalendarException::create([
            'resource_calendar_id' => $calendarId,
            'name' => $data['name'],
            'exception_type' => $data['exception_type'] ?? 'holiday',
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'is_working' => $data['is_working'] ?? false,
            'work_hours' => $data['work_hours'] ?? null,
            'is_recurring' => $data['is_recurring'] ?? false,
            'recurrence_pattern' => $data['recurrence_pattern'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Assign calendar to resource
     */
    public function assignToResource(int $calendarId, int $resourceId, array $data = []): ResourceCalendarAssignment
    {
        // Deactivate existing assignments if this is primary
        if ($data['is_primary'] ?? true) {
            ResourceCalendarAssignment::where('resource_id', $resourceId)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        return ResourceCalendarAssignment::create([
            'resource_calendar_id' => $calendarId,
            'resource_id' => $resourceId,
            'effective_from' => $data['effective_from'] ?? now(),
            'effective_to' => $data['effective_to'] ?? null,
            'is_primary' => $data['is_primary'] ?? true,
        ]);
    }

    /**
     * Get working days count between two dates
     */
    public function getWorkingDays(int $calendarId, $startDate, $endDate): int
    {
        $calendar = ResourceCalendar::with('exceptions')->findOrFail($calendarId);
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $workingDays = 0;

        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            if ($this->isWorkingDay($calendar, $date)) {
                $workingDays++;
            }
        }

        return $workingDays;
    }

    /**
     * Get working hours between two dates
     */
    public function getWorkingHours(int $calendarId, $startDate, $endDate): float
    {
        $calendar = ResourceCalendar::findOrFail($calendarId);
        $workingDays = $this->getWorkingDays($calendarId, $startDate, $endDate);
        
        return $workingDays * $calendar->hours_per_day;
    }

    /**
     * Check if a date is a working day
     */
    public function isWorkingDay(ResourceCalendar $calendar, Carbon $date): bool
    {
        // Check exceptions first
        $exception = $calendar->exceptions->first(function ($exc) use ($date) {
            $excStart = Carbon::parse($exc->start_date)->startOfDay();
            $excEnd = Carbon::parse($exc->end_date)->endOfDay();
            return $date->between($excStart, $excEnd);
        });

        if ($exception) {
            return $exception->is_working;
        }

        // Check regular work week
        $dayName = strtolower($date->format('l'));
        $workWeek = $calendar->work_week ?? [];
        
        return $workWeek[$dayName] ?? false;
    }

    /**
     * Calculate end date based on duration and calendar
     */
    public function calculateEndDate(int $calendarId, $startDate, int $durationDays): Carbon
    {
        $calendar = ResourceCalendar::with('exceptions')->findOrFail($calendarId);
        $current = Carbon::parse($startDate);
        $workingDaysCount = 0;

        while ($workingDaysCount < $durationDays) {
            if ($this->isWorkingDay($calendar, $current)) {
                $workingDaysCount++;
            }
            if ($workingDaysCount < $durationDays) {
                $current->addDay();
            }
        }

        return $current;
    }

    /**
     * Calculate duration in working days
     */
    public function calculateDuration(int $calendarId, $startDate, $endDate): int
    {
        return $this->getWorkingDays($calendarId, $startDate, $endDate);
    }

    /**
     * Get next working day from a date
     */
    public function getNextWorkingDay(int $calendarId, $date): Carbon
    {
        $calendar = ResourceCalendar::with('exceptions')->findOrFail($calendarId);
        $current = Carbon::parse($date);

        while (!$this->isWorkingDay($calendar, $current)) {
            $current->addDay();
        }

        return $current;
    }

    /**
     * Get previous working day from a date
     */
    public function getPreviousWorkingDay(int $calendarId, $date): Carbon
    {
        $calendar = ResourceCalendar::with('exceptions')->findOrFail($calendarId);
        $current = Carbon::parse($date);

        while (!$this->isWorkingDay($calendar, $current)) {
            $current->subDay();
        }

        return $current;
    }

    /**
     * Get calendar availability for a resource in a date range
     */
    public function getResourceAvailability(int $resourceId, $startDate, $endDate): array
    {
        $assignment = ResourceCalendarAssignment::where('resource_id', $resourceId)
            ->where('is_primary', true)
            ->whereNull('effective_to')
            ->orWhere('effective_to', '>=', now())
            ->with('calendar.exceptions')
            ->first();

        if (!$assignment) {
            return [];
        }

        $calendar = $assignment->calendar;
        $availability = [];
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            $dayInfo = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'is_working' => $this->isWorkingDay($calendar, $date),
                'hours' => 0,
                'exception' => null,
            ];

            if ($dayInfo['is_working']) {
                $dayInfo['hours'] = $calendar->hours_per_day;
            }

            // Check for exceptions
            $exception = $calendar->exceptions->first(function ($exc) use ($date) {
                $excStart = Carbon::parse($exc->start_date)->startOfDay();
                $excEnd = Carbon::parse($exc->end_date)->endOfDay();
                return $date->between($excStart, $excEnd);
            });

            if ($exception) {
                $dayInfo['exception'] = [
                    'name' => $exception->name,
                    'type' => $exception->exception_type,
                ];
                if ($exception->work_hours) {
                    $dayInfo['hours'] = $this->calculateHoursFromWorkHours($exception->work_hours);
                }
            }

            $availability[] = $dayInfo;
        }

        return $availability;
    }

    /**
     * Calculate hours from work hours array
     */
    protected function calculateHoursFromWorkHours(array $workHours): float
    {
        $start = Carbon::parse($workHours['start'] ?? '08:00');
        $end = Carbon::parse($workHours['end'] ?? '16:00');
        $breakStart = isset($workHours['break_start']) ? Carbon::parse($workHours['break_start']) : null;
        $breakEnd = isset($workHours['break_end']) ? Carbon::parse($workHours['break_end']) : null;

        $totalMinutes = $start->diffInMinutes($end);
        
        if ($breakStart && $breakEnd) {
            $breakMinutes = $breakStart->diffInMinutes($breakEnd);
            $totalMinutes -= $breakMinutes;
        }

        return $totalMinutes / 60;
    }

    /**
     * Copy calendar with exceptions
     */
    public function copyCalendar(int $calendarId, string $newName): ResourceCalendar
    {
        $original = ResourceCalendar::with('exceptions')->findOrFail($calendarId);

        $newCalendar = ResourceCalendar::create([
            'project_id' => $original->project_id,
            'name' => $newName,
            'description' => $original->description,
            'calendar_type' => $original->calendar_type,
            'time_zone' => $original->time_zone,
            'work_week' => $original->work_week,
            'work_hours' => $original->work_hours,
            'hours_per_day' => $original->hours_per_day,
            'hours_per_week' => $original->hours_per_week,
            'is_default' => false,
            'is_active' => true,
        ]);

        // Copy exceptions
        foreach ($original->exceptions as $exception) {
            ResourceCalendarException::create([
                'resource_calendar_id' => $newCalendar->id,
                'name' => $exception->name,
                'exception_type' => $exception->exception_type,
                'start_date' => $exception->start_date,
                'end_date' => $exception->end_date,
                'is_working' => $exception->is_working,
                'work_hours' => $exception->work_hours,
                'is_recurring' => $exception->is_recurring,
                'recurrence_pattern' => $exception->recurrence_pattern,
                'notes' => $exception->notes,
            ]);
        }

        return $newCalendar;
    }

    /**
     * Get calendar summary statistics
     */
    public function getCalendarStats(int $calendarId, int $year): array
    {
        $calendar = ResourceCalendar::with('exceptions')->findOrFail($calendarId);
        
        $startOfYear = Carbon::createFromDate($year, 1, 1);
        $endOfYear = Carbon::createFromDate($year, 12, 31);
        
        $totalDays = $startOfYear->diffInDays($endOfYear) + 1;
        $workingDays = $this->getWorkingDays($calendarId, $startOfYear, $endOfYear);
        $totalHolidays = $calendar->exceptions->where('exception_type', 'holiday')->count();
        
        return [
            'year' => $year,
            'total_days' => $totalDays,
            'working_days' => $workingDays,
            'non_working_days' => $totalDays - $workingDays,
            'holidays' => $totalHolidays,
            'total_working_hours' => $workingDays * $calendar->hours_per_day,
            'hours_per_day' => $calendar->hours_per_day,
            'hours_per_week' => $calendar->hours_per_week,
        ];
    }

    /**
     * Validate calendar configuration
     */
    public function validateCalendar(int $calendarId): array
    {
        $calendar = ResourceCalendar::with('exceptions')->findOrFail($calendarId);
        $errors = [];
        $warnings = [];

        // Check if at least one working day exists
        $workWeek = $calendar->work_week ?? [];
        $hasWorkingDay = collect($workWeek)->contains(true);
        if (!$hasWorkingDay) {
            $errors[] = 'التقويم لا يحتوي على أي يوم عمل.';
        }

        // Check hours per day
        if ($calendar->hours_per_day <= 0 || $calendar->hours_per_day > 24) {
            $errors[] = 'ساعات العمل اليومية غير صحيحة.';
        }

        // Check for overlapping exceptions
        $exceptions = $calendar->exceptions->sortBy('start_date');
        foreach ($exceptions as $index => $exc) {
            $excStart = Carbon::parse($exc->start_date);
            $excEnd = Carbon::parse($exc->end_date);
            
            foreach ($exceptions->slice($index + 1) as $other) {
                $otherStart = Carbon::parse($other->start_date);
                $otherEnd = Carbon::parse($other->end_date);
                
                if ($excStart->between($otherStart, $otherEnd) || $excEnd->between($otherStart, $otherEnd)) {
                    $warnings[] = "تداخل بين الاستثناء '{$exc->name}' و '{$other->name}'.";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
