<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MspTaskMapping extends Model
{
    use HasFactory;

    protected $table = 'msp_task_mappings';

    protected $fillable = [
        'msp_import_export_id',
        'msp_task_uid',
        'msp_task_name',
        'msp_outline_level',
        'msp_wbs_code',
        'gantt_task_id',
        'mapping_status',
        'msp_data',
    ];

    protected $casts = [
        'msp_data' => 'array',
    ];

    // Relationships
    public function importExport(): BelongsTo
    {
        return $this->belongsTo(MspImportExport::class, 'msp_import_export_id');
    }

    public function ganttTask(): BelongsTo
    {
        return $this->belongsTo(GanttTask::class);
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

    public function scopeSummaryTasks($query)
    {
        return $query->where('msp_outline_level', 0);
    }

    public function scopeDetailTasks($query)
    {
        return $query->where('msp_outline_level', '>', 0);
    }
}
