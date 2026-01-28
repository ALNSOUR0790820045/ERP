<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * علاقة Gantt (التبعية)
 * Gantt Dependency
 */
class GanttDependency extends Model
{
    use HasFactory;

    protected $fillable = [
        'predecessor_id',
        'successor_id',
        'dependency_type',
        'lag_days',
    ];

    // أنواع العلاقات
    const DEPENDENCY_TYPES = [
        'FS' => 'من النهاية إلى البداية (Finish-to-Start)',
        'FF' => 'من النهاية إلى النهاية (Finish-to-Finish)',
        'SS' => 'من البداية إلى البداية (Start-to-Start)',
        'SF' => 'من البداية إلى النهاية (Start-to-Finish)',
    ];

    // العلاقات
    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(GanttTask::class, 'predecessor_id');
    }

    public function successor(): BelongsTo
    {
        return $this->belongsTo(GanttTask::class, 'successor_id');
    }

    // Accessors
    public function getDependencyTypeLabelAttribute(): string
    {
        return self::DEPENDENCY_TYPES[$this->dependency_type] ?? $this->dependency_type;
    }

    /**
     * الحصول على بيانات للـ JavaScript
     */
    public function toGanttData(): array
    {
        return [
            'id' => $this->id,
            'source' => $this->predecessor_id,
            'target' => $this->successor_id,
            'type' => $this->getGanttLinkType(),
            'lag' => $this->lag_days,
        ];
    }

    /**
     * تحويل نوع العلاقة لـ Gantt
     */
    protected function getGanttLinkType(): int
    {
        return match($this->dependency_type) {
            'FS' => 0,
            'SS' => 1,
            'FF' => 2,
            'SF' => 3,
            default => 0,
        };
    }
}
