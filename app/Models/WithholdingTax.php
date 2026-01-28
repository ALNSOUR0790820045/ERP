<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithholdingTax extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_type', 'transaction_id', 'supplier_id', 'customer_id',
        'tax_date', 'gross_amount', 'tax_rate', 'tax_amount',
        'is_reported', 'report_date', 'report_reference', 'notes', 'created_by',
    ];

    protected $casts = [
        'tax_date' => 'date',
        'report_date' => 'date',
        'gross_amount' => 'decimal:3',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:3',
        'is_reported' => 'boolean',
    ];

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeUnreported($query) { return $query->where('is_reported', false); }
}
