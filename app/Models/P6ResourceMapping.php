<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class P6ResourceMapping extends Model
{
    use HasFactory;

    protected $table = 'p6_resource_mappings';

    protected $fillable = [
        'p6_import_export_id',
        'p6_resource_id',
        'p6_resource_name',
        'project_resource_id',
        'employee_id',
        'equipment_id',
        'resource_type',
        'mapping_status',
        'p6_data',
    ];

    protected $casts = [
        'p6_data' => 'array',
    ];

    // Relationships
    public function importExport(): BelongsTo
    {
        return $this->belongsTo(P6ImportExport::class, 'p6_import_export_id');
    }

    public function projectResource(): BelongsTo
    {
        return $this->belongsTo(ProjectResource::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    // Scopes
    public function scopeLabor($query)
    {
        return $query->where('resource_type', 'labor');
    }

    public function scopeEquipment($query)
    {
        return $query->where('resource_type', 'equipment');
    }

    public function scopeMaterial($query)
    {
        return $query->where('resource_type', 'material');
    }
}
