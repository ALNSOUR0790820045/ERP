<?php

namespace App\Models\Engineering\Quality;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityPlan extends Model
{
    protected $fillable = [
        'project_id',
        'plan_number',
        'name',
        'description',
        'discipline',
        'effective_date',
        'revision',
        'applicable_specifications',
        'inspection_points',
        'hold_points',
        'witness_points',
        'status',
        'prepared_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'applicable_specifications' => 'array',
        'inspection_points' => 'array',
        'hold_points' => 'array',
        'witness_points' => 'array',
        'approved_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function inspectionRequests(): HasMany
    {
        return $this->hasMany(InspectionRequest::class, 'quality_plan_id');
    }

    public function approve(int $userId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function createRevision(): self
    {
        $newPlan = $this->replicate();
        $newPlan->revision = (string)(intval($this->revision) + 1);
        $newPlan->status = 'draft';
        $newPlan->approved_by = null;
        $newPlan->approved_at = null;
        $newPlan->save();

        $this->update(['status' => 'superseded']);

        return $newPlan;
    }

    public static function generateNumber(int $projectId): string
    {
        $count = self::where('project_id', $projectId)->count() + 1;
        return sprintf('QP-%d-%03d', $projectId, $count);
    }

    public function getTotalInspectionPoints(): int
    {
        return count($this->hold_points ?? []) + count($this->witness_points ?? []);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByDiscipline($query, string $discipline)
    {
        return $query->where('discipline', $discipline);
    }
}
