<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BimModel extends Model
{
    use HasFactory;

    protected $table = 'bim_models';

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'model_type',
        'file_name',
        'file_path',
        'file_size',
        'file_format',
        'software_name',
        'software_version',
        'ifc_schema_version',
        'model_version',
        'lod',
        'georeferencing',
        'model_units',
        'elements_count',
        'is_active',
        'uploaded_by',
    ];

    protected $casts = [
        'georeferencing' => 'array',
        'model_units' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function elementLinks(): HasMany
    {
        return $this->hasMany(BimElementLink::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeArchitectural($query)
    {
        return $query->where('model_type', 'architectural');
    }

    public function scopeStructural($query)
    {
        return $query->where('model_type', 'structural');
    }

    public function scopeMep($query)
    {
        return $query->where('model_type', 'mep');
    }

    public function scopeIfc($query)
    {
        return $query->where('file_format', 'ifc');
    }

    public function scopeRevit($query)
    {
        return $query->where('file_format', 'rvt');
    }

    public function scopeNavisworks($query)
    {
        return $query->whereIn('file_format', ['nwd', 'nwc']);
    }

    // Methods
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function getModelTypeArabicAttribute(): string
    {
        $types = [
            'architectural' => 'معماري',
            'structural' => 'إنشائي',
            'mep' => 'ميكانيكا وكهرباء',
            'civil' => 'مدني',
            'combined' => 'مدمج',
            'coordination' => 'تنسيق',
        ];

        return $types[$this->model_type] ?? $this->model_type;
    }

    public function getLodDescriptionAttribute(): string
    {
        $lods = [
            '100' => 'مفاهيمي',
            '200' => 'تصميم أولي',
            '300' => 'تصميم تفصيلي',
            '350' => 'وثائق البناء',
            '400' => 'التصنيع',
            '500' => 'كما تم البناء',
        ];

        return $lods[$this->lod] ?? 'غير محدد';
    }

    public function getFileSizeFormattedAttribute(): string
    {
        if (!$this->file_size) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    public function getLinkedElementsCount(): int
    {
        return $this->elementLinks()->whereNotNull('gantt_task_id')->count();
    }

    public function getProgressPercentAttribute(): float
    {
        $total = $this->elements_count;
        if ($total == 0) {
            return 0;
        }

        $completed = $this->elementLinks()->where('status', 'completed')->count();
        return round(($completed / $total) * 100, 2);
    }
}
