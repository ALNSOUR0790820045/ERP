<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class P6ActivityMapping extends Model
{
    use HasFactory;

    protected $table = 'p6_activity_mappings';

    protected $fillable = [
        'p6_import_export_id',
        'p6_activity_id',
        'p6_activity_name',
        'gantt_task_id',
        'project_wbs_id',
        'mapping_status',
        'p6_data',
        'mapping_notes',
    ];

    protected $casts = [
        'p6_data' => 'array',
        'mapping_notes' => 'array',
    ];

    // Relationships
    public function importExport(): BelongsTo
    {
        return $this->belongsTo(P6ImportExport::class, 'p6_import_export_id');
    }

    public function ganttTask(): BelongsTo
    {
        return $this->belongsTo(GanttTask::class);
    }

    public function projectWbs(): BelongsTo
    {
        return $this->belongsTo(ProjectWbs::class);
    }

    // Scopes
    public function scopeMapped($query)
    {
        return $query->where('mapping_status', 'mapped');
    }

    public function scopeCreated($query)
    {
        return $query->where('mapping_status', 'created');
    }

    public function scopeSkipped($query)
    {
        return $query->where('mapping_status', 'skipped');
    }

    public function scopeWithErrors($query)
    {
        return $query->where('mapping_status', 'error');
    }
}
