<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MspImportExport extends Model
{
    use HasFactory;

    protected $table = 'msp_import_exports';

    protected $fillable = [
        'project_id',
        'type',
        'format',
        'file_name',
        'file_path',
        'file_size',
        'msp_version',
        'status',
        'total_tasks',
        'processed_tasks',
        'total_resources',
        'processed_resources',
        'errors_count',
        'error_log',
        'mapping_config',
        'options',
        'notes',
        'created_by',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'error_log' => 'array',
        'mapping_config' => 'array',
        'options' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function taskMappings(): HasMany
    {
        return $this->hasMany(MspTaskMapping::class);
    }

    // Scopes
    public function scopeImports($query)
    {
        return $query->where('type', 'import');
    }

    public function scopeExports($query)
    {
        return $query->where('type', 'export');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Methods
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(array $errors = []): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_log' => array_merge($this->error_log ?? [], $errors),
            'errors_count' => count($errors),
        ]);
    }

    public function getProgressPercentAttribute(): float
    {
        if ($this->total_tasks == 0) {
            return 0;
        }
        return round(($this->processed_tasks / $this->total_tasks) * 100, 2);
    }
}
