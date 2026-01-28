<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillOfMaterial extends Model
{
    use SoftDeletes;

    protected $table = 'bill_of_materials';

    protected $fillable = [
        'bom_number',
        'product_name',
        'product_name_en',
        'unit',
        'quantity',
        'version',
        'effective_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'quantity' => 'decimal:3',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(BomItem::class, 'bom_id');
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class, 'bom_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::whereYear('created_at', $year)->count() + 1;
        return "BOM-{$year}-" . str_pad($last, 5, '0', STR_PAD_LEFT);
    }
}
