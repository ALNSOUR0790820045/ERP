<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityChecklistItem extends Model
{
    protected $fillable = [
        'checklist_id', 'item_number', 'item_description',
        'acceptance_criteria', 'inspection_method', 'is_mandatory',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
    ];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(QualityChecklist::class, 'checklist_id');
    }
}
