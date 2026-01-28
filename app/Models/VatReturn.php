<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VatReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_number', 'period_from', 'period_to', 'due_date',
        'output_vat', 'input_vat', 'net_vat', 'adjustment_amount',
        'final_amount', 'payment_date', 'payment_reference',
        'status', 'submitted_date', 'notes', 'created_by', 'approved_by',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
        'submitted_date' => 'date',
        'output_vat' => 'decimal:3',
        'input_vat' => 'decimal:3',
        'net_vat' => 'decimal:3',
        'adjustment_amount' => 'decimal:3',
        'final_amount' => 'decimal:3',
    ];

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany { return $this->hasMany(VatReturnItem::class); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeSubmitted($query) { return $query->where('status', 'submitted'); }
}
