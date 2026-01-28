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

class Lease extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'lease_number',
        'lease_name',
        'lease_type',
        'asset_type',
        'asset_description',
        'lessor_id',
        'commencement_date',
        'end_date',
        'lease_term_months',
        'monthly_payment',
        'payment_frequency',
        'payment_timing',
        'incremental_borrowing_rate',
        'initial_direct_costs',
        'lease_incentives',
        'restoration_costs',
        'right_of_use_asset',
        'lease_liability',
        'accumulated_depreciation',
        'has_purchase_option',
        'purchase_option_price',
        'has_extension_option',
        'extension_period_months',
        'has_termination_option',
        'status',
        'rou_asset_account_id',
        'lease_liability_account_id',
        'depreciation_expense_account_id',
        'interest_expense_account_id',
        'created_by',
    ];

    protected $casts = [
        'commencement_date' => 'date',
        'end_date' => 'date',
        'monthly_payment' => 'decimal:2',
        'incremental_borrowing_rate' => 'decimal:4',
        'initial_direct_costs' => 'decimal:2',
        'lease_incentives' => 'decimal:2',
        'restoration_costs' => 'decimal:2',
        'right_of_use_asset' => 'decimal:2',
        'lease_liability' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'purchase_option_price' => 'decimal:2',
        'has_purchase_option' => 'boolean',
        'has_extension_option' => 'boolean',
        'has_termination_option' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lessor(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'lessor_id');
    }

    public function paymentSchedules(): HasMany
    {
        return $this->hasMany(LeasePaymentSchedule::class);
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(LeaseDepreciation::class);
    }

    public function modifications(): HasMany
    {
        return $this->hasMany(LeaseModification::class);
    }

    public function rouAssetAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'rou_asset_account_id');
    }

    public function leaseLiabilityAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'lease_liability_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'depreciation_expense_account_id');
    }

    public function interestExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'interest_expense_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Calculate initial ROU asset and lease liability
    public function calculateInitialRecognition(): array
    {
        $payments = [];
        $paymentAmount = $this->monthly_payment;
        $frequency = match($this->payment_frequency) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annual' => 6,
            'annual' => 12,
        };

        $numberOfPayments = ceil($this->lease_term_months / $frequency);
        $rate = $this->incremental_borrowing_rate / 100 / 12 * $frequency;

        // Calculate present value of lease payments
        $pv = 0;
        for ($i = 1; $i <= $numberOfPayments; $i++) {
            $discountFactor = pow(1 + $rate, -$i);
            $pv += $paymentAmount * $discountFactor;
        }

        $leaseLiability = $pv;
        $rouAsset = $leaseLiability + $this->initial_direct_costs - $this->lease_incentives + $this->restoration_costs;

        return [
            'lease_liability' => round($leaseLiability, 2),
            'right_of_use_asset' => round($rouAsset, 2),
        ];
    }

    public function getNetBookValueAttribute(): float
    {
        return $this->right_of_use_asset - $this->accumulated_depreciation;
    }

    public function getRemainingLiabilityAttribute(): float
    {
        $totalPaid = $this->paymentSchedules()
            ->where('status', 'paid')
            ->sum('principal_amount');
        return $this->lease_liability - $totalPaid;
    }
}
