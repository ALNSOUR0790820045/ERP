<?php

namespace App\Models\CRM\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesStage extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'sequence',
        'default_probability',
        'color',
        'description',
        'required_fields',
        'is_won_stage',
        'is_lost_stage',
        'is_active',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'default_probability' => 'integer',
        'required_fields' => 'array',
        'is_won_stage' => 'boolean',
        'is_lost_stage' => 'boolean',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function deals(): HasMany
    {
        return $this->hasMany(PipelineDeal::class, 'stage_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence');
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    // Methods
    public function isClosedStage(): bool
    {
        return $this->is_won_stage || $this->is_lost_stage;
    }

    public function getDealsCount(): int
    {
        return $this->deals()->where('status', 'open')->count();
    }

    public function getDealsValue(): float
    {
        return $this->deals()->where('status', 'open')->sum('deal_value');
    }
}
