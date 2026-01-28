<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForensicScheduleAnalysis extends Model
{
    use HasFactory;

    protected $table = 'forensic_schedule_analyses';

    protected $fillable = [
        'project_id',
        'analysis_number',
        'title',
        'description',
        'analysis_type',
        'methodology',
        'analysis_period_start',
        'analysis_period_end',
        'contract_completion_date',
        'actual_completion_date',
        'extended_completion_date',
        'total_delay_days',
        'contractor_delay_days',
        'owner_delay_days',
        'concurrent_delay_days',
        'excusable_delay_days',
        'compensable_delay_days',
        'delay_events',
        'critical_path_changes',
        'float_consumption',
        'findings',
        'recommendations',
        'status',
        'analyst_id',
        'reviewer_id',
    ];

    protected $casts = [
        'analysis_period_start' => 'date',
        'analysis_period_end' => 'date',
        'contract_completion_date' => 'date',
        'actual_completion_date' => 'date',
        'extended_completion_date' => 'date',
        'delay_events' => 'array',
        'critical_path_changes' => 'array',
        'float_consumption' => 'array',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function analyst(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analyst_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function delayEvents(): HasMany
    {
        return $this->hasMany(ForensicDelayEvent::class);
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFinal($query)
    {
        return $query->where('status', 'final');
    }

    public function scopeDelayAnalysis($query)
    {
        return $query->where('analysis_type', 'delay');
    }

    public function scopeDisruptionAnalysis($query)
    {
        return $query->where('analysis_type', 'disruption');
    }

    // Methods
    public function calculateTotalDelay(): void
    {
        $this->total_delay_days = $this->contractor_delay_days 
            + $this->owner_delay_days 
            - $this->concurrent_delay_days;
        $this->save();
    }

    public function recalculateFromEvents(): void
    {
        $events = $this->delayEvents;
        
        $this->contractor_delay_days = $events
            ->where('responsible_party', 'contractor')
            ->sum('net_delay_days');
            
        $this->owner_delay_days = $events
            ->where('responsible_party', 'owner')
            ->sum('net_delay_days');
            
        $this->concurrent_delay_days = $events
            ->sum('concurrent_delay_days');
            
        $this->excusable_delay_days = $events
            ->whereIn('delay_category', ['excusable_compensable', 'excusable_non_compensable'])
            ->sum('net_delay_days');
            
        $this->compensable_delay_days = $events
            ->where('delay_category', 'excusable_compensable')
            ->sum('net_delay_days');
            
        $this->calculateTotalDelay();
    }

    public function getAnalysisTypeArabicAttribute(): string
    {
        $types = [
            'delay' => 'تحليل التأخير',
            'disruption' => 'تحليل التعطيل',
            'acceleration' => 'تحليل التسريع',
            'loss_of_productivity' => 'تحليل فقدان الإنتاجية',
        ];

        return $types[$this->analysis_type] ?? $this->analysis_type;
    }

    public function getMethodologyArabicAttribute(): string
    {
        $methods = [
            'as_planned_vs_as_built' => 'المخطط مقابل المنفذ',
            'impacted_as_planned' => 'المخطط المتأثر',
            'collapsed_as_built' => 'التنفيذ المطوي',
            'time_impact' => 'تحليل التأثير الزمني',
            'windows' => 'تحليل النوافذ',
            'daily_delay' => 'تحليل التأخير اليومي',
        ];

        return $methods[$this->methodology] ?? $this->methodology;
    }

    public static function generateNumber(int $projectId): string
    {
        $count = self::where('project_id', $projectId)->count() + 1;
        return sprintf('FSA-%04d-%04d', $projectId, $count);
    }
}
