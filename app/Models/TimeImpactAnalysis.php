<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeImpactAnalysis extends Model
{
    use HasFactory;

    protected $table = 'time_impact_analyses';

    protected $fillable = [
        'project_id',
        'extension_of_time_id',
        'analysis_number',
        'title',
        'description',
        'delay_type',
        'analysis_method',
        'event_start_date',
        'event_end_date',
        'data_date',
        'baseline_completion_date',
        'impacted_completion_date',
        'delay_days',
        'concurrent_delay_days',
        'pacing_delay_days',
        'net_delay_days',
        'impacted_activities',
        'critical_path_before',
        'critical_path_after',
        'analysis_narrative',
        'conclusion',
        'status',
        'prepared_by',
        'reviewed_by',
        'approved_by',
        'submitted_at',
    ];

    protected $casts = [
        'event_start_date' => 'date',
        'event_end_date' => 'date',
        'data_date' => 'date',
        'baseline_completion_date' => 'date',
        'impacted_completion_date' => 'date',
        'impacted_activities' => 'array',
        'critical_path_before' => 'array',
        'critical_path_after' => 'array',
        'submitted_at' => 'datetime',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function extensionOfTime(): BelongsTo
    {
        return $this->belongsTo(ExtensionOfTime::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function fragments(): HasMany
    {
        return $this->hasMany(TimeImpactFragment::class);
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeInReview($query)
    {
        return $query->where('status', 'in_review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeExcusable($query)
    {
        return $query->whereIn('delay_type', ['excusable_compensable', 'excusable_non_compensable']);
    }

    public function scopeCompensable($query)
    {
        return $query->where('delay_type', 'excusable_compensable');
    }

    // Methods
    public function calculateNetDelay(): void
    {
        $this->net_delay_days = ($this->delay_days ?? 0) 
            - ($this->concurrent_delay_days ?? 0) 
            - ($this->pacing_delay_days ?? 0);
        $this->save();
    }

    public function submit(): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function approve(int $userId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
        ]);
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }

    public function getDelayTypeArabicAttribute(): string
    {
        $types = [
            'excusable_compensable' => 'تأخير معذور وقابل للتعويض',
            'excusable_non_compensable' => 'تأخير معذور غير قابل للتعويض',
            'non_excusable' => 'تأخير غير معذور',
            'concurrent' => 'تأخير متزامن',
        ];

        return $types[$this->delay_type] ?? $this->delay_type;
    }

    public function getAnalysisMethodArabicAttribute(): string
    {
        $methods = [
            'as_planned' => 'كما هو مخطط',
            'as_built' => 'كما تم التنفيذ',
            'impacted_as_planned' => 'المخطط المتأثر',
            'collapsed_as_built' => 'التنفيذ المطوي',
            'time_impact' => 'تحليل التأثير الزمني',
            'windows' => 'تحليل النوافذ',
        ];

        return $methods[$this->analysis_method] ?? $this->analysis_method;
    }

    public static function generateNumber(int $projectId): string
    {
        $count = self::where('project_id', $projectId)->count() + 1;
        return sprintf('TIA-%04d-%04d', $projectId, $count);
    }
}
