<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForensicDelayEvent extends Model
{
    use HasFactory;

    protected $table = 'forensic_delay_events';

    protected $fillable = [
        'forensic_schedule_analysis_id',
        'event_id',
        'event_name',
        'event_description',
        'responsible_party',
        'delay_category',
        'event_start_date',
        'event_end_date',
        'gross_delay_days',
        'concurrent_delay_days',
        'net_delay_days',
        'affected_activities',
        'critical_path_impact',
        'cost_impact',
        'supporting_documents',
        'mitigation_efforts',
    ];

    protected $casts = [
        'event_start_date' => 'date',
        'event_end_date' => 'date',
        'affected_activities' => 'array',
        'critical_path_impact' => 'boolean',
        'cost_impact' => 'decimal:2',
    ];

    // Relationships
    public function forensicAnalysis(): BelongsTo
    {
        return $this->belongsTo(ForensicScheduleAnalysis::class, 'forensic_schedule_analysis_id');
    }

    // Scopes
    public function scopeContractorResponsible($query)
    {
        return $query->where('responsible_party', 'contractor');
    }

    public function scopeOwnerResponsible($query)
    {
        return $query->where('responsible_party', 'owner');
    }

    public function scopeThirdParty($query)
    {
        return $query->where('responsible_party', 'third_party');
    }

    public function scopeForceMajeure($query)
    {
        return $query->where('responsible_party', 'force_majeure');
    }

    public function scopeConcurrent($query)
    {
        return $query->where('responsible_party', 'concurrent');
    }

    public function scopeExcusable($query)
    {
        return $query->whereIn('delay_category', ['excusable_compensable', 'excusable_non_compensable']);
    }

    public function scopeCompensable($query)
    {
        return $query->where('delay_category', 'excusable_compensable');
    }

    public function scopeNonExcusable($query)
    {
        return $query->where('delay_category', 'non_excusable');
    }

    public function scopeCriticalPathImpact($query)
    {
        return $query->where('critical_path_impact', true);
    }

    // Methods
    public function calculateNetDelay(): void
    {
        $this->net_delay_days = $this->gross_delay_days - $this->concurrent_delay_days;
        $this->save();
    }

    public function getResponsiblePartyArabicAttribute(): string
    {
        $parties = [
            'contractor' => 'المقاول',
            'owner' => 'المالك',
            'third_party' => 'طرف ثالث',
            'force_majeure' => 'قوة قاهرة',
            'concurrent' => 'متزامن',
        ];

        return $parties[$this->responsible_party] ?? $this->responsible_party;
    }

    public function getDelayCategoryArabicAttribute(): string
    {
        $categories = [
            'excusable_compensable' => 'معذور وقابل للتعويض',
            'excusable_non_compensable' => 'معذور غير قابل للتعويض',
            'non_excusable' => 'غير معذور',
            'concurrent' => 'متزامن',
        ];

        return $categories[$this->delay_category] ?? $this->delay_category;
    }

    public function getEventDurationAttribute(): int
    {
        if (!$this->event_start_date || !$this->event_end_date) {
            return 0;
        }
        return $this->event_start_date->diffInDays($this->event_end_date);
    }

    public function isCompensable(): bool
    {
        return $this->delay_category === 'excusable_compensable';
    }

    public function isExcusable(): bool
    {
        return in_array($this->delay_category, ['excusable_compensable', 'excusable_non_compensable']);
    }
}
