<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractVariationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'variation_id',
        'contract_item_id',
        'item_number',
        'description',
        'unit_id',
        'unit_code',
        'quantity',
        'pricing_method',
        'unit_rate',
        'total_amount',
        'is_addition',
        'justification',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'unit_rate' => 'decimal:3',
        'total_amount' => 'decimal:3',
        'is_addition' => 'boolean',
    ];

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ContractVariation::class, 'variation_id');
    }

    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
