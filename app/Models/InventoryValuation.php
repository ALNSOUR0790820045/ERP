<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryValuation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'warehouse_id', 'valuation_date', 'valuation_method',
        'total_quantity', 'total_value', 'items_data', 'status', 'created_by',
    ];

    protected $casts = [
        'valuation_date' => 'date',
        'total_quantity' => 'decimal:4',
        'total_value' => 'decimal:3',
        'items_data' => 'array',
    ];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeFinal($query) { return $query->where('status', 'final'); }
}
