<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WbsDependency extends Model
{
    protected $fillable = [
        'predecessor_id',
        'successor_id',
        'dependency_type',
        'lag_days',
    ];

    protected $casts = [
        'lag_days' => 'integer',
    ];

    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(ProjectWbs::class, 'predecessor_id');
    }

    public function successor(): BelongsTo
    {
        return $this->belongsTo(ProjectWbs::class, 'successor_id');
    }

    public function getDependencyTypeNameAttribute(): string
    {
        return match($this->dependency_type) {
            'FS' => 'انتهاء - بداية',
            'SS' => 'بداية - بداية',
            'FF' => 'انتهاء - انتهاء',
            'SF' => 'بداية - انتهاء',
            default => $this->dependency_type,
        };
    }
}
