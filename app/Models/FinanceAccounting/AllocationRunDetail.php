<?php

namespace App\Models\FinanceAccounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllocationRunDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'allocation_run_id',
        'allocation_target_id',
        'basis_amount',
        'allocated_amount',
        'allocation_percentage',
    ];

    protected $casts = [
        'basis_amount' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'allocation_percentage' => 'decimal:4',
    ];

    public function allocationRun(): BelongsTo
    {
        return $this->belongsTo(AllocationRun::class);
    }

    public function allocationTarget(): BelongsTo
    {
        return $this->belongsTo(AllocationTarget::class);
    }
}
