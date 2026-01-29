<?php

namespace App\Models\CRM\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesPipeline extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description',
        'pipeline_type',
        'stage_ids',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'stage_ids' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function deals(): HasMany
    {
        return $this->hasMany(PipelineDeal::class, 'pipeline_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    // Methods
    public function getStages()
    {
        return SalesStage::whereIn('id', $this->stage_ids ?? [])
            ->active()
            ->ordered()
            ->get();
    }

    public function getTotalValue(): float
    {
        return $this->deals()->where('status', 'open')->sum('deal_value');
    }

    public function getWeightedValue(): float
    {
        return $this->deals()->where('status', 'open')->sum('weighted_value');
    }

    public function getWonDeals()
    {
        return $this->deals()->where('status', 'won')->get();
    }

    public function getWonValue(): float
    {
        return $this->deals()->where('status', 'won')->sum('deal_value');
    }

    public function getConversionRate(): float
    {
        $total = $this->deals()->whereIn('status', ['won', 'lost'])->count();
        $won = $this->deals()->where('status', 'won')->count();
        
        return $total > 0 ? ($won / $total) * 100 : 0;
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }
}
