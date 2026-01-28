<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\ProjectPriority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contract_id',
        'company_id',
        'branch_id',
        'project_number',
        'code',
        'name_ar',
        'name_en',
        'description',
        'project_type_id',
        'priority',
        'customer_id',
        'consultant_id',
        'country_id',
        'city_id',
        'address',
        'latitude',
        'longitude',
        'site_area',
        'building_area',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'duration_days',
        'working_days_per_week',
        'working_hours_per_day',
        'contract_value',
        'budget',
        'actual_cost',
        'currency_id',
        'project_manager_id',
        'site_engineer_id',
        'safety_officer_id',
        'quality_officer_id',
        'accountant_id',
        'planned_progress',
        'actual_progress',
        'status',
        'pv',
        'ev',
        'ac',
        'spi',
        'cpi',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => ProjectStatus::class,
        'priority' => ProjectPriority::class,
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'contract_value' => 'decimal:3',
        'budget' => 'decimal:3',
        'actual_cost' => 'decimal:3',
        'planned_progress' => 'decimal:2',
        'actual_progress' => 'decimal:2',
        'working_hours_per_day' => 'decimal:2',
        'site_area' => 'decimal:2',
        'building_area' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'pv' => 'decimal:3',
        'ev' => 'decimal:3',
        'ac' => 'decimal:3',
        'spi' => 'decimal:4',
        'cpi' => 'decimal:4',
    ];

    // Relationships
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'customer_id');
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function siteEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'site_engineer_id');
    }

    public function safetyOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'safety_officer_id');
    }

    public function qualityOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quality_officer_id');
    }

    public function accountant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accountant_id');
    }

    public function wbsItems(): HasMany
    {
        return $this->hasMany(ProjectWbs::class)->orderBy('sort_order');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(ProjectResource::class);
    }

    public function progressUpdates(): HasMany
    {
        return $this->hasMany(ProjectProgressUpdate::class);
    }

    public function baselines(): HasMany
    {
        return $this->hasMany(ProjectBaseline::class);
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(ProjectDailyReport::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?? $this->name_ar);
    }

    public function getProgressVarianceAttribute(): float
    {
        return $this->actual_progress - $this->planned_progress;
    }

    public function getCostVarianceAttribute(): float
    {
        return $this->budget - $this->actual_cost;
    }

    public function getScheduleVarianceAttribute(): ?float
    {
        if ($this->ev === null || $this->pv === null) {
            return null;
        }
        return $this->ev - $this->pv;
    }

    public function getRemainingDaysAttribute(): int
    {
        if (!$this->planned_end_date) {
            return 0;
        }
        return now()->diffInDays($this->planned_end_date, false);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', ProjectStatus::ACTIVE);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
