<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityInspection extends Model
{
    protected $fillable = [
        'project_id', 'checklist_id', 'inspection_number', 'inspection_date',
        'inspection_type', 'location', 'work_activity', 'status', 'result',
        'inspector_id', 'witnessed_by', 'findings', 'recommendations',
    ];

    protected $casts = [
        'inspection_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(QualityChecklist::class, 'checklist_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function witnessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'witnessed_by');
    }

    public function results(): HasMany
    {
        return $this->hasMany(QualityInspectionResult::class, 'inspection_id');
    }

    public function ncrs(): HasMany
    {
        return $this->hasMany(NonConformanceReport::class, 'inspection_id');
    }
}
