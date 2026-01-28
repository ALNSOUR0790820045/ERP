<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoqItem extends Model
{
    protected $fillable = [
        'tender_id',
        'parent_id',
        'item_number',
        'level',
        'description_ar',
        'description_en',
        'unit_id',
        'quantity',
        'unit_rate',
        'total_amount',
        'material_cost',
        'labor_cost',
        'equipment_cost',
        'subcontractor_cost',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'level' => 'integer',
        'quantity' => 'decimal:3',
        'unit_rate' => 'decimal:3',
        'total_amount' => 'decimal:3',
        'material_cost' => 'decimal:3',
        'labor_cost' => 'decimal:3',
        'equipment_cost' => 'decimal:3',
        'subcontractor_cost' => 'decimal:3',
        'sort_order' => 'integer',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(BoqItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(BoqItem::class, 'parent_id')->orderBy('sort_order');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(BoqMaterial::class);
    }

    public function labor(): HasMany
    {
        return $this->hasMany(BoqLabor::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(BoqEquipment::class);
    }

    public function subcontractors(): HasMany
    {
        return $this->hasMany(BoqSubcontractor::class);
    }

    public function getDescriptionAttribute(): string
    {
        return app()->getLocale() === 'en' && $this->description_en 
            ? $this->description_en 
            : $this->description_ar;
    }

    public function calculateCosts(): void
    {
        $this->material_cost = $this->materials()->sum('total_cost');
        $this->labor_cost = $this->labor()->sum('total_cost');
        $this->equipment_cost = $this->equipment()->sum('total_cost');
        $this->subcontractor_cost = $this->subcontractors()->sum('total_cost');
        
        $this->unit_rate = $this->material_cost + $this->labor_cost + 
                          $this->equipment_cost + $this->subcontractor_cost;
        
        if ($this->quantity > 0) {
            $this->unit_rate = $this->unit_rate / $this->quantity;
        }
        
        $this->total_amount = $this->unit_rate * $this->quantity;
        $this->save();
    }

    public function getIsParentAttribute(): bool
    {
        return $this->children()->exists();
    }

    public function getFullItemNumberAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_item_number . '.' . $this->item_number;
        }
        return $this->item_number;
    }
}
