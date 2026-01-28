<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Enums\FidicType;
use App\Enums\PricingMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tender_id',
        'company_id',
        'contract_number',
        'name_ar',
        'name_en',
        'description',
        'scope_of_work',
        'contract_type',
        'fidic_type',
        'pricing_method',
        'employer_id',
        'employer_name',
        'employer_representative',
        'employer_contact',
        'engineer_id',
        'engineer_name',
        'engineer_representative',
        'contractor_name',
        'contractor_representative',
        'site_manager',
        'award_date',
        'signing_date',
        'commencement_date',
        'original_completion_date',
        'current_completion_date',
        'original_duration_days',
        'current_duration_days',
        'defects_liability_months',
        'provisional_acceptance_date',
        'final_acceptance_date',
        'original_value',
        'current_value',
        'currency_id',
        'exchange_rate',
        'vat_percentage',
        'vat_included',
        'advance_payment_percentage',
        'advance_payment_amount',
        'advance_recovery_method',
        'advance_recovery_start',
        'advance_recovery_rate',
        'retention_percentage',
        'retention_limit_percentage',
        'first_retention_release',
        'final_retention_release',
        'payment_terms_days',
        'billing_cycle',
        'performance_bond_percentage',
        'performance_bond_amount',
        'performance_bond_type',
        'performance_bond_validity',
        'advance_bond_percentage',
        'advance_bond_amount',
        'advance_bond_validity',
        'car_insurance_required',
        'third_party_insurance',
        'professional_liability',
        'price_adjustment_applicable',
        'price_adjustment_formula',
        'base_date',
        'threshold_percentage',
        'adjustment_indices',
        'liquidated_damages_rate',
        'liquidated_damages_max',
        'bonus_rate',
        'force_majeure_clause',
        'dispute_resolution',
        'governing_law',
        'arbitration_rules',
        'special_conditions',
        'status',
        'completion_percentage',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'contract_type' => ContractType::class,
        'fidic_type' => FidicType::class,
        'pricing_method' => PricingMethod::class,
        'status' => ContractStatus::class,
        'award_date' => 'date',
        'signing_date' => 'date',
        'commencement_date' => 'date',
        'original_completion_date' => 'date',
        'current_completion_date' => 'date',
        'provisional_acceptance_date' => 'date',
        'final_acceptance_date' => 'date',
        'performance_bond_validity' => 'date',
        'advance_bond_validity' => 'date',
        'base_date' => 'date',
        'original_value' => 'decimal:3',
        'current_value' => 'decimal:3',
        'exchange_rate' => 'decimal:6',
        'vat_percentage' => 'decimal:2',
        'vat_included' => 'boolean',
        'advance_payment_percentage' => 'decimal:2',
        'advance_payment_amount' => 'decimal:3',
        'advance_recovery_start' => 'decimal:2',
        'advance_recovery_rate' => 'decimal:2',
        'retention_percentage' => 'decimal:2',
        'retention_limit_percentage' => 'decimal:2',
        'first_retention_release' => 'decimal:2',
        'final_retention_release' => 'decimal:2',
        'performance_bond_percentage' => 'decimal:2',
        'performance_bond_amount' => 'decimal:3',
        'advance_bond_percentage' => 'decimal:2',
        'advance_bond_amount' => 'decimal:3',
        'car_insurance_required' => 'boolean',
        'third_party_insurance' => 'boolean',
        'professional_liability' => 'boolean',
        'price_adjustment_applicable' => 'boolean',
        'threshold_percentage' => 'decimal:2',
        'adjustment_indices' => 'array',
        'liquidated_damages_rate' => 'decimal:4',
        'liquidated_damages_max' => 'decimal:2',
        'bonus_rate' => 'decimal:4',
        'completion_percentage' => 'decimal:2',
    ];

    // Relationships
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'employer_id');
    }

    public function engineer(): BelongsTo
    {
        return $this->belongsTo(Consultant::class, 'engineer_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class)->orderBy('sort_order');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ContractVariation::class);
    }

    public function bonds(): HasMany
    {
        return $this->hasMany(ContractBond::class);
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(ContractExtension::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(ContractClaim::class);
    }

    public function insurances(): HasMany
    {
        return $this->hasMany(ContractInsurance::class);
    }

    public function subcontracts(): HasMany
    {
        return $this->hasMany(ContractSubcontract::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ContractDocument::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ContractEvent::class)->orderByDesc('event_date');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?? $this->name_ar);
    }

    public function getTotalVariationsAttribute(): float
    {
        return $this->variations()
            ->where('status', 'approved')
            ->sum('approved_amount');
    }

    public function getRemainingDaysAttribute(): int
    {
        if (!$this->current_completion_date) {
            return 0;
        }
        return now()->diffInDays($this->current_completion_date, false);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', ContractStatus::ACTIVE);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('contract_type', $type);
    }
}
