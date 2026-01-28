<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id', 'warehouse_id', 'min_quantity', 'max_quantity',
        'safety_quantity', 'lead_time_days', 'daily_usage',
        'auto_reorder',
    ];

    protected $casts = [
        'min_quantity' => 'decimal:4',
        'max_quantity' => 'decimal:4',
        'safety_quantity' => 'decimal:4',
        'lead_time_days' => 'decimal:1',
        'daily_usage' => 'decimal:4',
        'auto_reorder' => 'boolean',
    ];

    public function material(): BelongsTo { return $this->belongsTo(Material::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }

    public function scopeAutoReorder($query) { return $query->where('auto_reorder', true); }
}
