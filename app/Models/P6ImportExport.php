<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class P6ImportExport extends Model
{
    use HasFactory;

    protected $table = 'p6_import_exports';

    protected $fillable = [
        'project_id',
        'type',
        'format',
        'file_name',
        'file_path',
        'file_size',
        'status',
        'total_activities',
        'processed_activities',
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

    public function activityMappings(): HasMany
    {
        return $this->hasMany(P6ActivityMapping::class);
    }

    public function resourceMappings(): HasMany
    {
        return $this->hasMany(P6ResourceMapping::class);
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

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
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
        if ($this->total_activities == 0) {
            return 0;
        }
        return round(($this->processed_activities / $this->total_activities) * 100, 2);
    }

    public function addError(string $message, ?string $context = null): void
    {
        $errors = $this->error_log ?? [];
        $errors[] = [
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->toIso8601String(),
        ];
        $this->update([
            'error_log' => $errors,
            'errors_count' => count($errors),
        ]);
    }
}
