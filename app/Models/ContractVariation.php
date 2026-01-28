<?php

namespace App\Models;

use App\Enums\VariationStatus;
use App\Enums\VariationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractVariation extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'vo_number',
        'vo_type',
        'title',
        'description',
        'reason',
        'requested_by',
        'request_date',
        'instruction_reference',
        'drawings_affected',
        'submitted_amount',
        'approved_amount',
        'time_extension_days',
        'status',
        'approved_by',
        'approval_date',
        'approval_reference',
        'approval_notes',
        'created_by',
    ];

    protected $casts = [
        'vo_type' => VariationType::class,
        'status' => VariationStatus::class,
        'request_date' => 'date',
        'approval_date' => 'date',
        'submitted_amount' => 'decimal:3',
        'approved_amount' => 'decimal:3',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractVariationItem::class, 'variation_id');
    }

    public function extension(): HasMany
    {
        return $this->hasMany(ContractExtension::class, 'variation_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getNetAmountAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->is_addition ? $item->total_amount : -$item->total_amount;
        });
    }
}
