<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id', 'method', 'standard_cost', 'last_purchase_cost',
        'average_cost', 'last_cost_update',
    ];

    protected $casts = [
        'last_cost_update' => 'date',
        'standard_cost' => 'decimal:4',
        'last_purchase_cost' => 'decimal:4',
        'average_cost' => 'decimal:4',
    ];

    public function material(): BelongsTo { return $this->belongsTo(Material::class); }

    public function getCurrentCostAttribute(): float
    {
        return match($this->method) {
            'standard' => $this->standard_cost ?? 0,
            'weighted_average' => $this->average_cost ?? 0,
            default => $this->last_purchase_cost ?? $this->average_cost ?? 0,
        };
    }
}
