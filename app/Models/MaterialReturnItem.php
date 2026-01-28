<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_return_id', 'material_id', 'quantity',
        'unit_cost', 'total_cost', 'condition', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:3',
    ];

    public function materialReturn(): BelongsTo { return $this->belongsTo(MaterialReturn::class); }
    public function material(): BelongsTo { return $this->belongsTo(Material::class); }

    public function scopeGood($query) { return $query->where('condition', 'good'); }
    public function scopeDamaged($query) { return $query->where('condition', 'damaged'); }
}
