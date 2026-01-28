<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * مجموعة الحقول المخصصة
 * Custom Field Group
 */
class CustomFieldGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'description',
        'entity_type',
        'sort_order',
        'is_active',
        'is_collapsible',
        'is_collapsed_by_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_collapsible' => 'boolean',
        'is_collapsed_by_default' => 'boolean',
    ];

    // العلاقات
    public function definitions(): HasMany
    {
        return $this->hasMany(CustomFieldDefinition::class, 'group_id')
            ->orderBy('sort_order');
    }

    public function activeDefinitions(): HasMany
    {
        return $this->definitions()->where('is_active', true);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        return app()->getLocale() === 'ar' && $this->name_ar 
            ? $this->name_ar 
            : $this->name;
    }

    // Methods
    /**
     * الحصول على مجموعات الحقول لكيان معين
     */
    public static function getForEntity(string $entityType): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->forEntity($entityType)
            ->with('activeDefinitions')
            ->orderBy('sort_order')
            ->get();
    }
}
