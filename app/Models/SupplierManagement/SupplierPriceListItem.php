<?php

namespace App\Models\SupplierManagement;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPriceListItem extends Model
{
    protected $fillable = [
        'supplier_price_list_id', 'product_id', 'item_code', 'item_name', 'description',
        'unit_of_measure', 'unit_price', 'currency', 'min_order_quantity', 'max_order_quantity',
        'price_breaks', 'discount_percentage', 'discounted_price', 'lead_time_days',
        'effective_date', 'expiry_date', 'is_active', 'notes', 'specifications', 'metadata',
    ];

    protected $casts = [
        'unit_price' => 'decimal:4', 'min_order_quantity' => 'decimal:3',
        'max_order_quantity' => 'decimal:3', 'discount_percentage' => 'decimal:2',
        'discounted_price' => 'decimal:4', 'price_breaks' => 'array',
        'effective_date' => 'date', 'expiry_date' => 'date',
        'is_active' => 'boolean', 'specifications' => 'array', 'metadata' => 'array',
    ];

    public function priceList(): BelongsTo { return $this->belongsTo(SupplierPriceList::class, 'supplier_price_list_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    public function scopeActive($q) { return $q->where('is_active', true); }

    /**
     * Get effective price after discount
     */
    public function getEffectivePrice(): float {
        if ($this->discounted_price && $this->discounted_price > 0) {
            return $this->discounted_price;
        }
        if ($this->discount_percentage && $this->discount_percentage > 0) {
            return $this->unit_price * (1 - ($this->discount_percentage / 100));
        }
        return $this->unit_price;
    }

    /**
     * Get price based on quantity with price breaks
     */
    public function getPriceForQuantity(float $quantity): float {
        if (empty($this->price_breaks)) return $this->getEffectivePrice();
        
        $applicablePrice = $this->getEffectivePrice();
        $priceBreaks = collect($this->price_breaks)->sortBy('quantity');
        
        foreach ($priceBreaks as $break) {
            if ($quantity >= ($break['quantity'] ?? 0)) {
                $applicablePrice = $break['price'] ?? $applicablePrice;
            }
        }
        return $applicablePrice;
    }

    /**
     * Calculate total for quantity
     */
    public function calculateTotal(float $quantity): float {
        return $this->getPriceForQuantity($quantity) * $quantity;
    }

    public function isValidForQuantity(float $quantity): bool {
        if ($this->min_order_quantity && $quantity < $this->min_order_quantity) return false;
        if ($this->max_order_quantity && $quantity > $this->max_order_quantity) return false;
        return true;
    }

    public function isCurrentlyValid(): bool {
        $now = now();
        if ($this->effective_date && $this->effective_date > $now) return false;
        if ($this->expiry_date && $this->expiry_date < $now) return false;
        return $this->is_active;
    }
}
