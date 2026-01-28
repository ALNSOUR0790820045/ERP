<?php

namespace App\Models\SupplierManagement;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlanketAgreementItem extends Model
{
    protected $fillable = [
        'blanket_agreement_id', 'line_number', 'product_id', 'item_code', 'item_name',
        'description', 'unit_of_measure', 'unit_price', 'currency', 'min_quantity',
        'max_quantity', 'released_quantity', 'remaining_quantity', 'min_order_quantity',
        'price_breaks', 'lead_time_days', 'specifications', 'notes', 'status', 'metadata',
    ];

    protected $casts = [
        'unit_price' => 'decimal:4', 'min_quantity' => 'decimal:3', 'max_quantity' => 'decimal:3',
        'released_quantity' => 'decimal:3', 'remaining_quantity' => 'decimal:3',
        'min_order_quantity' => 'decimal:3', 'price_breaks' => 'array',
        'specifications' => 'array', 'metadata' => 'array',
    ];

    public function blanketAgreement(): BelongsTo { return $this->belongsTo(BlanketPurchaseAgreement::class, 'blanket_agreement_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function releases(): HasMany { return $this->hasMany(BlanketAgreementRelease::class, 'blanket_item_id'); }

    /**
     * Get price based on quantity with price breaks
     */
    public function getPriceForQuantity(float $quantity): float {
        if (empty($this->price_breaks)) return $this->unit_price;
        
        $applicablePrice = $this->unit_price;
        foreach ($this->price_breaks as $break) {
            if ($quantity >= ($break['quantity'] ?? 0)) {
                $applicablePrice = $break['price'] ?? $this->unit_price;
            }
        }
        return $applicablePrice;
    }

    public function canRelease(float $quantity): bool {
        return ($this->released_quantity + $quantity) <= $this->max_quantity;
    }

    public function release(float $quantity): void {
        $this->increment('released_quantity', $quantity);
        $this->decrement('remaining_quantity', $quantity);
    }

    public function scopeAvailable($q) { return $q->where('status', 'active')->where('remaining_quantity', '>', 0); }

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->line_number) {
                $model->line_number = BlanketAgreementItem::where('blanket_agreement_id', $model->blanket_agreement_id)->max('line_number') + 1;
            }
            $model->remaining_quantity = $model->max_quantity - ($model->released_quantity ?? 0);
        });
    }
}
