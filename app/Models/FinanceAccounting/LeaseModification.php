<?php

namespace App\Models\FinanceAccounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseModification extends Model
{
    use HasFactory;

    protected $fillable = [
        'lease_id',
        'modification_date',
        'modification_type',
        'description',
        'revised_lease_liability',
        'rou_asset_adjustment',
        'gain_loss',
        'journal_voucher_id',
        'created_by',
    ];

    protected $casts = [
        'modification_date' => 'date',
        'revised_lease_liability' => 'decimal:2',
        'rou_asset_adjustment' => 'decimal:2',
        'gain_loss' => 'decimal:2',
    ];

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function journalVoucher(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
