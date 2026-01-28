<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomItem extends Model
{
    protected $fillable = [
        'bom_id',
        'material_id',
        'item_name',
        'quantity',
        'unit',
        'wastage_percent',
        'net_quantity',
        'is_optional',
        'substitute_id',
        'sequence',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'wastage_percent' => 'decimal:2',
        'net_quantity' => 'decimal:4',
        'is_optional' => 'boolean',
    ];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function substitute(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'substitute_id');
    }

    protected static function booted(): void
    {
        static::saving(function ($item) {
            if ($item->quantity && $item->wastage_percent) {
                $item->net_quantity = $item->quantity * (1 + $item->wastage_percent / 100);
            }
        });
    }
}
