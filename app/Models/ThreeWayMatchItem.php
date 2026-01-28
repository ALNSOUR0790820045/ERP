<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThreeWayMatchItem extends Model
{
    protected $fillable = [
        'three_way_match_id',
        'material_id',
        'item_description',
        'po_quantity',
        'po_unit_price',
        'po_total',
        'grn_quantity',
        'grn_total',
        'invoice_quantity',
        'invoice_unit_price',
        'invoice_total',
        'quantity_variance',
        'price_variance',
        'variance_type',
        'is_matched',
    ];

    protected $casts = [
        'po_quantity' => 'decimal:3',
        'po_unit_price' => 'decimal:2',
        'po_total' => 'decimal:2',
        'grn_quantity' => 'decimal:3',
        'grn_total' => 'decimal:2',
        'invoice_quantity' => 'decimal:3',
        'invoice_unit_price' => 'decimal:2',
        'invoice_total' => 'decimal:2',
        'quantity_variance' => 'decimal:3',
        'price_variance' => 'decimal:2',
        'is_matched' => 'boolean',
    ];

    public function threeWayMatch(): BelongsTo
    {
        return $this->belongsTo(ThreeWayMatch::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function calculateVariance(): void
    {
        $this->quantity_variance = $this->invoice_quantity - $this->grn_quantity;
        $this->price_variance = ($this->invoice_unit_price - $this->po_unit_price) * $this->invoice_quantity;
        
        if ($this->quantity_variance > 0) {
            $this->variance_type = 'over';
        } elseif ($this->quantity_variance < 0) {
            $this->variance_type = 'under';
        } elseif ($this->price_variance != 0) {
            $this->variance_type = 'price';
        } else {
            $this->variance_type = 'none';
            $this->is_matched = true;
        }
        
        $this->save();
    }
}
