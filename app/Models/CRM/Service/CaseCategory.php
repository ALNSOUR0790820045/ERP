<?php

namespace App\Models\CRM\Service;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseCategory extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'parent_id',
        'description',
        'default_priority',
        'default_sla_hours',
        'is_active',
    ];

    protected $casts = [
        'default_priority' => 'integer',
        'default_sla_hours' => 'integer',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function parent(): BelongsTo
    {
        return $this->belongsTo(CaseCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CaseCategory::class, 'parent_id');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(ServiceCase::class, 'category_id');
    }

    public function knowledgeArticles(): HasMany
    {
        return $this->hasMany(KnowledgeBase::class, 'category_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }

    // Methods
    public function getCasesCount(): int
    {
        return $this->cases()->count();
    }

    public function getOpenCasesCount(): int
    {
        return $this->cases()->whereNotIn('status', ['closed', 'resolved'])->count();
    }
}
