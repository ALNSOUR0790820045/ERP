<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * خط أساس Gantt
 * Gantt Baseline
 */
class GanttBaseline extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'baseline_date',
        'is_current',
        'tasks_snapshot',
        'created_by',
    ];

    protected $casts = [
        'baseline_date' => 'date',
        'is_current' => 'boolean',
        'tasks_snapshot' => 'array',
    ];

    // العلاقات
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    // Methods
    /**
     * إنشاء خط أساس جديد
     */
    public static function createFromProject(Project $project, string $name, ?string $description = null): self
    {
        // الحصول على جميع المهام
        $tasks = GanttTask::forProject($project->id)->get();
        
        $snapshot = $tasks->map(fn($task) => [
            'id' => $task->id,
            'name' => $task->name,
            'planned_start' => $task->planned_start->toDateString(),
            'planned_end' => $task->planned_end->toDateString(),
            'duration_days' => $task->duration_days,
            'progress' => $task->progress,
            'weight' => $task->weight,
        ])->toArray();

        return static::create([
            'project_id' => $project->id,
            'name' => $name,
            'description' => $description,
            'baseline_date' => now(),
            'is_current' => true,
            'tasks_snapshot' => $snapshot,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * تعيين كخط أساس حالي
     */
    public function setAsCurrent(): bool
    {
        // إلغاء الحالي
        static::where('project_id', $this->project_id)
            ->where('id', '!=', $this->id)
            ->update(['is_current' => false]);

        $this->is_current = true;
        return $this->save();
    }

    /**
     * الحصول على فرق المهمة
     */
    public function getTaskVariance(GanttTask $task): array
    {
        $baseline = collect($this->tasks_snapshot)
            ->firstWhere('id', $task->id);

        if (!$baseline) {
            return ['start_variance' => 0, 'end_variance' => 0, 'duration_variance' => 0];
        }

        return [
            'start_variance' => $task->planned_start->diffInDays($baseline['planned_start'], false),
            'end_variance' => $task->planned_end->diffInDays($baseline['planned_end'], false),
            'duration_variance' => $task->duration_days - $baseline['duration_days'],
        ];
    }
}
