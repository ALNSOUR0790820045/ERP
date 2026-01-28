<?php

namespace App\Models\SupplierManagement;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierPriceList extends Model
{
    protected $fillable = [
        'supplier_id', 'price_list_code', 'name', 'description', 'price_list_type',
        'currency', 'effective_date', 'expiry_date', 'status', 'is_default', 'version',
        'min_order_value', 'discount_type', 'discount_value', 'payment_terms',
        'delivery_terms', 'incoterms', 'created_by', 'approved_by', 'approved_at',
        'notes', 'metadata',
    ];

    protected $casts = [
        'effective_date' => 'date', 'expiry_date' => 'date', 'approved_at' => 'datetime',
        'is_default' => 'boolean', 'min_order_value' => 'decimal:2',
        'discount_value' => 'decimal:2', 'metadata' => 'array',
    ];

    const TYPE_STANDARD = 'standard';
    const TYPE_CONTRACT = 'contract';
    const TYPE_PROMOTIONAL = 'promotional';
    const TYPE_SPECIAL = 'special';

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_SUPERSEDED = 'superseded';

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany { return $this->hasMany(SupplierPriceListItem::class); }

    public function scopeActive($q) {
        return $q->where('status', self::STATUS_ACTIVE)
                 ->where('effective_date', '<=', now())
                 ->where(fn($q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()));
    }

    public function scopeDefault($q) { return $q->where('is_default', true); }

    public function approve(User $approver): void {
        $this->update(['status' => self::STATUS_APPROVED, 'approved_by' => $approver->id, 'approved_at' => now()]);
    }

    public function activate(): void {
        if ($this->is_default) {
            static::where('supplier_id', $this->supplier_id)->where('id', '!=', $this->id)
                  ->where('is_default', true)->update(['is_default' => false]);
        }
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    public function getPrice(string $itemCode): ?float {
        return $this->items()->where('item_code', $itemCode)->first()?->unit_price;
    }

    public function createNewVersion(): self {
        $newList = $this->replicate();
        $newList->version = $this->version + 1;
        $newList->status = self::STATUS_DRAFT;
        $newList->approved_by = null;
        $newList->approved_at = null;
        $newList->save();
        
        foreach ($this->items as $item) {
            $newItem = $item->replicate();
            $newItem->supplier_price_list_id = $newList->id;
            $newItem->save();
        }
        
        $this->update(['status' => self::STATUS_SUPERSEDED]);
        return $newList;
    }

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->price_list_code)) {
                $model->price_list_code = 'SPL-' . ($model->supplier_id ?? '0') . '-' . date('Ymd') . '-' . str_pad(static::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            }
            $model->version = $model->version ?? 1;
        });
    }
}
