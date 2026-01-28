<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractSubcontractItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'subcontract_id',
        'contract_item_id',
        'item_number',
        'description',
        'unit_id',
        'quantity',
        'unit_rate',
        'total_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'unit_rate' => 'decimal:3',
        'total_amount' => 'decimal:3',
    ];

    public function subcontract(): BelongsTo
    {
        return $this->belongsTo(ContractSubcontract::class, 'subcontract_id');
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
