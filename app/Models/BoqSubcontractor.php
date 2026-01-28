<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoqSubcontractor extends Model
{
    protected $fillable = [
        'boq_item_id',
        'subcontractor_name',
        'description',
        'scope_of_work',
        'unit_id',
        'quantity',
        'unit_rate',
        'total_cost',
        'payment_terms',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'unit_rate' => 'decimal:3',
        'total_cost' => 'decimal:3',
    ];

    public function boqItem(): BelongsTo
    {
        return $this->belongsTo(BoqItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    protected static function booted(): void
    {
        static::saving(function (BoqSubcontractor $sub) {
            $sub->total_cost = $sub->quantity * $sub->unit_rate;
        });

        static::saved(function (BoqSubcontractor $sub) {
            $sub->boqItem->calculateCosts();
        });

        static::deleted(function (BoqSubcontractor $sub) {
            $sub->boqItem->calculateCosts();
        });
    }
}
