<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractExtension extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'variation_id',
        'extension_number',
        'reason',
        'description',
        'requested_days',
        'approved_days',
        'new_completion_date',
        'status',
        'approved_by',
        'approval_date',
    ];

    protected $casts = [
        'new_completion_date' => 'date',
        'approval_date' => 'date',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ContractVariation::class, 'variation_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
