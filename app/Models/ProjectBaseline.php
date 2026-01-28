<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBaseline extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'baseline_number',
        'name',
        'baseline_date',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'baseline_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(ProjectBaselineDetail::class, 'baseline_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
