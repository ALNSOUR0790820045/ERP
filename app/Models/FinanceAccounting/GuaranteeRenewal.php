<?php

namespace App\Models\FinanceAccounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuaranteeRenewal extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_guarantee_id',
        'renewal_number',
        'renewal_date',
        'old_expiry_date',
        'new_expiry_date',
        'old_amount',
        'new_amount',
        'renewal_fees',
        'notes',
        'status',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'renewal_date' => 'date',
        'old_expiry_date' => 'date',
        'new_expiry_date' => 'date',
        'old_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
        'renewal_fees' => 'decimal:2',
    ];

    public function bankGuarantee(): BelongsTo
    {
        return $this->belongsTo(BankGuarantee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approve(int $approverId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approverId,
        ]);

        // Update parent guarantee
        $this->bankGuarantee->update([
            'expiry_date' => $this->new_expiry_date,
            'amount' => $this->new_amount,
        ]);
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }
}
