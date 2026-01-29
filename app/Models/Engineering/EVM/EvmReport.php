<?php

namespace App\Models\Engineering\EVM;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvmReport extends Model
{
    protected $fillable = [
        'report_number',
        'project_id',
        'snapshot_id',
        'title',
        'report_date',
        'period_from',
        'period_to',
        'executive_summary',
        'schedule_analysis',
        'cost_analysis',
        'risks_issues',
        'recommendations',
        'status',
        'prepared_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
        'approved_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(EvmSnapshot::class, 'snapshot_id');
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approve(int $userId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public static function generateNumber(int $projectId): string
    {
        $count = self::where('project_id', $projectId)->count() + 1;
        return sprintf('EVM-RPT-%d-%04d', $projectId, $count);
    }
}
