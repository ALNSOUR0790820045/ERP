<?php

namespace App\Models\CRM\Lead;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadSource extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'category',
        'description',
        'conversion_rate',
        'cost_per_lead',
        'is_active',
    ];

    protected $casts = [
        'conversion_rate' => 'decimal:2',
        'cost_per_lead' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'source_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    // Methods
    public function updateConversionRate(): void
    {
        $total = $this->leads()->count();
        $converted = $this->leads()->where('status', 'converted')->count();
        
        $this->update([
            'conversion_rate' => $total > 0 ? ($converted / $total) * 100 : 0
        ]);
    }
}
