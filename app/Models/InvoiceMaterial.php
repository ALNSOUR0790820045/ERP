<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceMaterial extends Model
{
    protected $fillable = [
        'invoice_id', 'material_name', 'material_code', 'description', 'quantity',
        'unit_id', 'unit_price', 'total_value', 'claim_percentage', 'claimed_amount', 'delivery_notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4', 'unit_price' => 'decimal:3', 'total_value' => 'decimal:3',
        'claim_percentage' => 'decimal:2', 'claimed_amount' => 'decimal:3',
    ];

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function unit(): BelongsTo { return $this->belongsTo(Unit::class); }
}
