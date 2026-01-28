<?php

namespace App\Models;

use App\Enums\InvoiceType;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id', 'contract_id', 'company_id', 'invoice_number', 'sequence_number',
        'invoice_type', 'period_from', 'period_to', 'valuation_date', 'reference',
        'cumulative_works', 'cumulative_variations', 'cumulative_materials', 'cumulative_gross',
        'previous_works', 'previous_variations', 'previous_materials', 'previous_gross',
        'current_works', 'current_variations', 'current_materials', 'current_gross',
        'advance_recovery', 'retention_deduction', 'income_tax', 'contractor_union_fee',
        'liquidated_damages', 'other_deductions', 'total_deductions',
        'net_amount', 'vat_amount', 'final_amount', 'currency_id', 'exchange_rate',
        'price_adjustment_applied', 'price_adjustment_amount', 'status',
        'submission_date', 'certification_date', 'payment_due_date', 'payment_date',
        'submitted_by', 'reviewed_by', 'approved_by', 'approval_notes',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'invoice_type' => InvoiceType::class,
        'status' => InvoiceStatus::class,
        'period_from' => 'date',
        'period_to' => 'date',
        'valuation_date' => 'date',
        'submission_date' => 'date',
        'certification_date' => 'date',
        'payment_due_date' => 'date',
        'payment_date' => 'date',
        'price_adjustment_applied' => 'boolean',
        'cumulative_works' => 'decimal:3',
        'current_works' => 'decimal:3',
        'current_gross' => 'decimal:3',
        'total_deductions' => 'decimal:3',
        'net_amount' => 'decimal:3',
        'final_amount' => 'decimal:3',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }
    public function materials(): HasMany { return $this->hasMany(InvoiceMaterial::class); }
    public function payments(): HasMany { return $this->hasMany(InvoicePayment::class); }
    public function submitter(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function getTotalPaidAttribute(): float
    {
        return $this->payments->sum('amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return $this->final_amount - $this->total_paid;
    }
}
