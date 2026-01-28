<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dashboard extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'name_en',
        'description',
        'type',
        'layout',
        'is_default',
        'is_public',
        'is_active',
        'refresh_interval',
        'created_by',
    ];

    protected $casts = [
        'layout' => 'array',
        'is_default' => 'boolean',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function widgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class)->orderBy('position_y')->orderBy('position_x');
    }

    public function users(): HasMany
    {
        return $this->hasMany(UserDashboard::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
