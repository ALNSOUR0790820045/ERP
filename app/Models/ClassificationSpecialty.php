<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassificationSpecialty extends Model
{
    protected $fillable = [
        'classification_field_id',
        'name_ar',
        'name_en',
        'code',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * المجال الذي ينتمي إليه الاختصاص
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(ClassificationField::class, 'classification_field_id');
    }

    /**
     * المناقصات المرتبطة بهذا الاختصاص
     */
    public function tenders(): HasMany
    {
        return $this->hasMany(Tender::class);
    }
}
