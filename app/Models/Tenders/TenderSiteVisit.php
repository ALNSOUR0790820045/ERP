<?php

namespace App\Models\Tenders;

use App\Models\Tender;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * زيارات موقع العطاء
 * Tender Site Visits
 */
class TenderSiteVisit extends Model
{
    protected $fillable = [
        'tender_id',
        'visit_date',
        'visit_end_time',
        'visitors',
        'site_location',
        'site_area',
        'access_conditions',
        'terrain_description',
        'existing_structures',
        'utilities_available',
        'nearby_facilities',
        'potential_issues',
        'weather_conditions',
        'photos',
        'visit_report_path',
        'site_rating',
        'recommendations',
        'owner_representative_present',
        'owner_representative_name',
        'is_mandatory',
        'created_by',
    ];

    protected $casts = [
        'visit_date' => 'datetime',
        'visit_end_time' => 'datetime',
        'visitors' => 'array',
        'photos' => 'array',
        'site_area' => 'decimal:2',
        'owner_representative_present' => 'boolean',
        'is_mandatory' => 'boolean',
    ];

    // تقييم الموقع
    public const SITE_RATINGS = [
        'excellent' => 'ممتاز',
        'good' => 'جيد',
        'fair' => 'مقبول',
        'poor' => 'ضعيف',
    ];

    // العلاقات
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('visit_date');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('visit_date', '>', now());
    }

    // Methods
    public function getDurationInHours(): ?float
    {
        if (!$this->visit_end_time) {
            return null;
        }
        return $this->visit_date->diffInHours($this->visit_end_time);
    }

    // Accessors
    public function getSiteRatingLabelAttribute(): ?string
    {
        return self::SITE_RATINGS[$this->site_rating] ?? $this->site_rating;
    }

    public function getVisitorsListAttribute(): array
    {
        return $this->visitors ?? [];
    }

    public function getPhotosListAttribute(): array
    {
        return $this->photos ?? [];
    }
}
