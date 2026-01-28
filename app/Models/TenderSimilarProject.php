<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * المشاريع المماثلة (الخبرة السابقة)
 * Tender Similar Projects - نموذج الخبرة من نماذج العرض
 */
class TenderSimilarProject extends Model
{
    protected $fillable = [
        'technical_proposal_id',
        'project_name',
        'client_name',
        'client_contact',
        'client_phone',
        'client_email',
        'location',
        'contract_value',
        'currency_id',
        'start_date',
        'completion_date',
        'actual_completion_date',
        'scope_of_work',
        'consultant_name',
        'participation_percentage',
        'completion_certificate_path',
        'contract_copy_path',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'contract_value' => 'decimal:3',
        'start_date' => 'date',
        'completion_date' => 'date',
        'actual_completion_date' => 'date',
        'participation_percentage' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function technicalProposal(): BelongsTo
    {
        return $this->belongsTo(TenderTechnicalProposal::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * حساب مدة المشروع بالأشهر
     */
    public function getDurationMonthsAttribute(): ?int
    {
        if (!$this->start_date || !$this->completion_date) {
            return null;
        }
        return $this->start_date->diffInMonths($this->completion_date);
    }

    /**
     * هل تم إنجاز المشروع في الوقت المحدد
     */
    public function getIsOnTimeAttribute(): ?bool
    {
        if (!$this->completion_date || !$this->actual_completion_date) {
            return null;
        }
        return $this->actual_completion_date <= $this->completion_date;
    }
}
