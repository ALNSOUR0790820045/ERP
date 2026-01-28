<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceMaterialOnSite extends Model
{
    protected $table = 'invoice_materials_on_site';

    protected $fillable = [
        'invoice_id',
        'material_id',
        'description',
        'quantity',
        'unit_price',
        'total_value',
        'claim_percentage',
        'claimed_value',
        'delivery_note_ref',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:3',
        'total_value' => 'decimal:3',
        'claim_percentage' => 'decimal:2',
        'claimed_value' => 'decimal:3',
    ];

    protected static function booted(): void
    {
        static::saving(function ($model) {
            $model->total_value = $model->quantity * $model->unit_price;
            $model->claimed_value = $model->total_value * ($model->claim_percentage / 100);
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
