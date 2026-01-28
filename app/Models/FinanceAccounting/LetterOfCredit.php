<?php

namespace App\Models\FinanceAccounting;

use App\Models\User;
use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LetterOfCredit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'supplier_id',
        'purchase_order_id',
        'lc_number',
        'lc_type',
        'lc_name',
        'issuing_bank_id',
        'issuing_bank_name',
        'advising_bank',
        'confirming_bank',
        'beneficiary_name',
        'beneficiary_bank',
        'issue_date',
        'expiry_date',
        'latest_shipment_date',
        'lc_amount',
        'currency_id',
        'tolerance_percentage',
        'is_confirmed',
        'is_transferable',
        'partial_shipment_allowed',
        'transhipment_allowed',
        'goods_description',
        'port_of_loading',
        'port_of_discharge',
        'incoterms',
        'required_documents',
        'margin_amount',
        'commission_amount',
        'utilized_amount',
        'available_amount',
        'status',
        'terms_and_conditions',
        'document_path',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'latest_shipment_date' => 'date',
        'lc_amount' => 'decimal:2',
        'tolerance_percentage' => 'decimal:2',
        'margin_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'utilized_amount' => 'decimal:2',
        'available_amount' => 'decimal:2',
        'is_confirmed' => 'boolean',
        'is_transferable' => 'boolean',
        'partial_shipment_allowed' => 'boolean',
        'transhipment_allowed' => 'boolean',
        'required_documents' => 'array',
        'approved_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Procurement\PurchaseOrder::class);
    }

    public function issuingBank(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'issuing_bank_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(LcAmendment::class, 'letter_of_credit_id');
    }

    public function utilizations(): HasMany
    {
        return $this->hasMany(LcUtilization::class, 'letter_of_credit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function calculateAvailableAmount(): float
    {
        $maxAmount = $this->lc_amount * (1 + $this->tolerance_percentage / 100);
        return max(0, $maxAmount - $this->utilized_amount);
    }

    public function updateAvailableAmount(): void
    {
        $this->utilized_amount = $this->utilizations()
            ->whereIn('status', ['accepted', 'paid'])
            ->sum('amount');
        $this->available_amount = $this->calculateAvailableAmount();
        $this->save();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }
        return now()->diffInDays($this->expiry_date, false);
    }

    public function getUtilizationPercentageAttribute(): float
    {
        return $this->lc_amount > 0 
            ? round(($this->utilized_amount / $this->lc_amount) * 100, 2) 
            : 0;
    }

    public static function generateLcNumber(): string
    {
        $prefix = 'LC';
        $year = date('Y');
        $lastLc = static::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
        $sequence = $lastLc ? intval(substr($lastLc->lc_number, -5)) + 1 : 1;
        return $prefix . $year . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }
}
