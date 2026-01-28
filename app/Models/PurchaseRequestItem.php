<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestItem extends Model
{
    protected $fillable = [
        'request_id', 'item_code', 'description', 'quantity', 'unit_id', 'estimated_price', 'specifications',
    ];

    protected $casts = ['quantity' => 'decimal:4', 'estimated_price' => 'decimal:3'];

    public function request(): BelongsTo { return $this->belongsTo(PurchaseRequest::class, 'request_id'); }
    public function unit(): BelongsTo { return $this->belongsTo(Unit::class); }
}
