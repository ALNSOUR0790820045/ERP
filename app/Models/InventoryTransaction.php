<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'warehouse_id', 'material_id', 'transaction_type', 'transaction_number',
        'transaction_date', 'reference_type', 'reference_id', 'quantity',
        'unit_cost', 'total_cost', 'balance_before', 'balance_after',
        'notes', 'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:3',
        'total_cost' => 'decimal:3',
        'balance_before' => 'decimal:4',
        'balance_after' => 'decimal:4',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
