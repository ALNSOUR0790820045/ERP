<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractSubcontract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'subcontractor_id',
        'subcontract_number',
        'subcontractor_name',
        'scope_of_work',
        'value',
        'currency_id',
        'start_date',
        'end_date',
        'retention_percentage',
        'payment_terms_days',
        'status',
    ];

    protected $casts = [
        'value' => 'decimal:3',
        'start_date' => 'date',
        'end_date' => 'date',
        'retention_percentage' => 'decimal:2',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractSubcontractItem::class, 'subcontract_id');
    }
}
