<?php

namespace App\Models\Engineering\Quality;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionRequest extends Model
{
    protected $fillable = [
        'request_number',
        'project_id',
        'quality_plan_id',
        'work_activity',
        'location',
        'discipline',
        'description',
        'inspection_type',
        'requested_date',
        'requested_time',
        'requested_by',
        'status',
        'inspection_date',
        'inspector_id',
        'result',
        'comments',
        'attachments',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'requested_time' => 'datetime',
        'inspection_date' => 'date',
        'attachments' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function qualityPlan(): BelongsTo
    {
        return $this->belongsTo(QualityPlan::class, 'quality_plan_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function schedule(int $inspectorId, $date): void
    {
        $this->update([
            'status' => 'scheduled',
            'inspector_id' => $inspectorId,
            'inspection_date' => $date,
        ]);
    }

    public function complete(string $result, string $comments = null): void
    {
        $this->update([
            'status' => 'completed',
            'result' => $result,
            'comments' => $comments,
            'inspection_date' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public static function generateNumber(int $projectId): string
    {
        $count = self::where('project_id', $projectId)->count() + 1;
        return sprintf('IR-%d-%04d', $projectId, $count);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'scheduled']);
    }

    public function scopeHoldPoints($query)
    {
        return $query->where('inspection_type', 'hold_point');
    }

    public function scopeByInspector($query, int $inspectorId)
    {
        return $query->where('inspector_id', $inspectorId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('inspection_date', now());
    }
}
