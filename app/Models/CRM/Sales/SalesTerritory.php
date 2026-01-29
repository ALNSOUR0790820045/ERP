<?php

namespace App\Models\CRM\Sales;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesTerritory extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'parent_id',
        'territory_type',
        'geographic_coverage',
        'industry_coverage',
        'manager_id',
        'target_revenue',
        'is_active',
    ];

    protected $casts = [
        'geographic_coverage' => 'array',
        'industry_coverage' => 'array',
        'target_revenue' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function parent(): BelongsTo
    {
        return $this->belongsTo(SalesTerritory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(SalesTerritory::class, 'parent_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function quotas(): HasMany
    {
        return $this->hasMany(SalesQuota::class, 'territory_id');
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

    public function scopeByType($query, string $type)
    {
        return $query->where('territory_type', $type);
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
    public function getAllDescendants()
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }
        
        return $descendants;
    }

    public function getAchievedRevenue(int $year, ?int $month = null): float
    {
        return PipelineDeal::where('status', 'won')
            ->whereYear('actual_close_date', $year)
            ->when($month, fn($q) => $q->whereMonth('actual_close_date', $month))
            ->whereHas('customer', function ($q) {
                // يجب تحديد العلاقة بين العميل والمنطقة
            })
            ->sum('deal_value');
    }
}
