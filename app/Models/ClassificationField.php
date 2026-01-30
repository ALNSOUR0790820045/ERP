<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassificationField extends Model
{
    protected $fillable = [
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
     * الاختصاصات التابعة لهذا المجال
     */
    public function specialties(): HasMany
    {
        return $this->hasMany(ClassificationSpecialty::class);
    }

    /**
     * المناقصات المرتبطة بهذا المجال
     */
    public function tenders(): HasMany
    {
        return $this->hasMany(Tender::class);
    }
}
