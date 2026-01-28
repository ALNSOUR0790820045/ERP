<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalAccountItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'final_account_id', 'item_type', 'boq_item_id', 'variation_id',
        'description', 'unit', 'contract_quantity', 'final_quantity',
        'quantity_variance', 'contract_rate', 'final_rate', 'rate_variance',
        'contract_amount', 'final_amount', 'amount_variance', 'notes',
    ];

    protected $casts = [
        'contract_quantity' => 'decimal:4',
        'final_quantity' => 'decimal:4',
        'quantity_variance' => 'decimal:4',
        'contract_rate' => 'decimal:4',
        'final_rate' => 'decimal:4',
        'rate_variance' => 'decimal:4',
        'contract_amount' => 'decimal:3',
        'final_amount' => 'decimal:3',
        'amount_variance' => 'decimal:3',
    ];

    public function finalAccount(): BelongsTo { return $this->belongsTo(FinalAccount::class); }
    public function boqItem(): BelongsTo { return $this->belongsTo(BoqItem::class); }
    public function variation(): BelongsTo { return $this->belongsTo(Variation::class); }
}
