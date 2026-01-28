<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityInspectionResult extends Model
{
    protected $fillable = [
        'inspection_id', 'checklist_item_id', 'item_description', 'result', 'remarks',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(QualityInspection::class, 'inspection_id');
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(QualityChecklistItem::class, 'checklist_item_id');
    }
}
