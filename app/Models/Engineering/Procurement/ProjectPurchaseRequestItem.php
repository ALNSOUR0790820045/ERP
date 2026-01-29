<?php

namespace App\Models\Engineering\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectPurchaseRequestItem extends Model
{
    protected $fillable = [
        'request_id',
        'line_number',
        'item_code',
        'description',
        'specification',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
        'required_date',
        'notes',
    ];

    protected $casts = [
        'line_number' => 'integer',
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'total_price' => 'decimal:2',
        'required_date' => 'date',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ProjectPurchaseRequest::class, 'request_id');
    }

    protected static function booted()
    {
        static::saving(function ($item) {
            if ($item->quantity && $item->unit_price) {
                $item->total_price = $item->quantity * $item->unit_price;
            }
        });

        static::saved(function ($item) {
            $item->request->estimated_value = $item->request->calculateTotal();
            $item->request->save();
        });
    }
}
