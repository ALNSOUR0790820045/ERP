<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardWidget extends Model
{
    protected $fillable = [
        'dashboard_id',
        'title',
        'widget_type',
        'chart_type',
        'data_source',
        'query_config',
        'display_config',
        'width',
        'height',
        'position_x',
        'position_y',
        'icon',
        'color',
        'is_visible',
        'refresh_interval',
    ];

    protected $casts = [
        'query_config' => 'array',
        'display_config' => 'array',
        'is_visible' => 'boolean',
    ];

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function getWidgetTypeNameAttribute(): string
    {
        return match($this->widget_type) {
            'kpi_card' => 'بطاقة مؤشر',
            'chart' => 'رسم بياني',
            'table' => 'جدول',
            'list' => 'قائمة',
            'progress' => 'شريط تقدم',
            'gauge' => 'عداد',
            'map' => 'خريطة',
            'calendar' => 'تقويم',
            'timeline' => 'جدول زمني',
            'custom' => 'مخصص',
            default => $this->widget_type,
        };
    }
}
