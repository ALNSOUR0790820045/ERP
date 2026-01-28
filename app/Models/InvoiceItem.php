<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'contract_item_id', 'item_number', 'description', 'unit_id',
        'contract_qty', 'contract_rate', 'contract_amount',
        'previous_qty', 'previous_amount', 'current_qty', 'current_amount',
        'cumulative_qty', 'cumulative_amount', 'remaining_qty', 'completion_percentage',
    ];

    protected $casts = [
        'contract_qty' => 'decimal:6', 'contract_rate' => 'decimal:3', 'contract_amount' => 'decimal:3',
        'previous_qty' => 'decimal:6', 'previous_amount' => 'decimal:3',
        'current_qty' => 'decimal:6', 'current_amount' => 'decimal:3',
        'cumulative_qty' => 'decimal:6', 'cumulative_amount' => 'decimal:3',
        'remaining_qty' => 'decimal:6', 'completion_percentage' => 'decimal:2',
    ];

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function contractItem(): BelongsTo { return $this->belongsTo(ContractItem::class); }
    public function unit(): BelongsTo { return $this->belongsTo(Unit::class); }
}
