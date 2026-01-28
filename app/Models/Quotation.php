<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'quotation_number',
        'quotation_date',
        'valid_until',
        'customer_id',
        'opportunity_id',
        'subject',
        'description',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'currency',
        'terms_conditions',
        'status',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items()->sum('total_price');
        $this->total_amount = $this->subtotal - $this->discount_amount + $this->tax_amount;
        $this->save();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::whereYear('created_at', $year)->count() + 1;
        return "QUO-{$year}-" . str_pad($last, 5, '0', STR_PAD_LEFT);
    }
}
