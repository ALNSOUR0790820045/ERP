<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePayment extends Model
{
    protected $fillable = [
        'invoice_id', 'payment_number', 'payment_date', 'amount', 'payment_method',
        'reference', 'bank_name', 'cheque_number', 'notes', 'created_by',
    ];

    protected $casts = ['payment_date' => 'date', 'amount' => 'decimal:3'];

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
