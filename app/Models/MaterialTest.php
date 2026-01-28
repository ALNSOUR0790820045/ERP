<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialTest extends Model
{
    protected $fillable = [
        'project_id', 'material_id', 'test_number', 'test_date', 'test_type',
        'sample_id', 'testing_lab', 'sample_date', 'sample_location',
        'test_parameters', 'test_results', 'acceptance_criteria',
        'result', 'remarks', 'status',
    ];

    protected $casts = [
        'test_date' => 'date',
        'sample_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
