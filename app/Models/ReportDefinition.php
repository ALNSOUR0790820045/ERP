<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportDefinition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'name_en',
        'category',
        'type',
        'description',
        'model_class',
        'parameters',
        'columns',
        'filters',
        'groupings',
        'charts',
        'template',
        'frequency',
        'is_active',
        'is_system',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'parameters' => 'array',
        'columns' => 'array',
        'filters' => 'array',
        'groupings' => 'array',
        'charts' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function executions(): HasMany
    {
        return $this->hasMany(ReportExecution::class);
    }

    public function scheduledReports(): HasMany
    {
        return $this->hasMany(ScheduledReport::class);
    }

    public function favoriteBy(): HasMany
    {
        return $this->hasMany(UserFavoriteReport::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCategoryNameAttribute(): string
    {
        return match($this->category) {
            'projects' => 'المشاريع',
            'contracts' => 'العقود',
            'finance' => 'المالية',
            'hr' => 'الموارد البشرية',
            'warehouse' => 'المستودعات',
            'equipment' => 'المعدات',
            'quality' => 'الجودة',
            'hse' => 'السلامة',
            'procurement' => 'المشتريات',
            'crm' => 'العملاء',
            'executive' => 'تنفيذي',
            default => $this->category,
        };
    }
}
