<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BinCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id', 'warehouse_id', 'transaction_date', 'transaction_type',
        'reference_type', 'reference_id', 'reference_number',
        'quantity_in', 'quantity_out', 'balance',
        'unit_cost', 'total_cost', 'notes', 'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'quantity_in' => 'decimal:4',
        'quantity_out' => 'decimal:4',
        'balance' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:3',
    ];

    public function material(): BelongsTo { return $this->belongsTo(Material::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeIn($query) { return $query->where('transaction_type', 'in'); }
    public function scopeOut($query) { return $query->where('transaction_type', 'out'); }
}
