<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityChecklist extends Model
{
    protected $fillable = [
        'company_id', 'code', 'name_ar', 'name_en',
        'checklist_type', 'description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QualityChecklistItem::class, 'checklist_id');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(QualityInspection::class, 'checklist_id');
    }
}
