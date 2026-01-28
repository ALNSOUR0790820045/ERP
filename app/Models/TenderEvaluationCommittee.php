<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * لجان التقييم
 * Tender Evaluation Committees - لجان الفتح والتقييم
 */
class TenderEvaluationCommittee extends Model
{
    protected $fillable = [
        'tender_id',
        'committee_type',
        'committee_name',
        'members',
        'meeting_date',
        'meeting_location',
        'findings',
        'recommendations',
        'minutes_path',
        'status',
    ];

    protected $casts = [
        'members' => 'array',
        'meeting_date' => 'date',
    ];

    // أنواع اللجان
    public const TYPES = [
        'opening' => 'لجنة فتح المظاريف',
        'technical_evaluation' => 'لجنة التقييم الفني',
        'financial_evaluation' => 'لجنة التقييم المالي',
        'final' => 'لجنة الترسية النهائية',
    ];

    // حالات اللجنة
    public const STATUSES = [
        'scheduled' => 'مجدولة',
        'completed' => 'مكتملة',
        'adjourned' => 'مؤجلة',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->committee_type] ?? $this->committee_type;
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * عدد أعضاء اللجنة
     */
    public function getMembersCountAttribute(): int
    {
        return is_array($this->members) ? count($this->members) : 0;
    }
}
