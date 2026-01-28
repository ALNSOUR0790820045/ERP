<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceAdjustmentCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'interim_payment_id', 'progress_certificate_id',
        'calculation_date', 'formula_type', 'base_date', 'current_date',
        'labor_weight', 'material_weight', 'equipment_weight', 'fixed_weight',
        'labor_index_base', 'labor_index_current',
        'material_index_base', 'material_index_current',
        'equipment_index_base', 'equipment_index_current',
        'adjustment_factor', 'eligible_value', 'adjustment_amount',
        'notes',
    ];

    protected $casts = [
        'calculation_date' => 'date',
        'base_date' => 'date',
        'current_date' => 'date',
        'labor_weight' => 'decimal:4',
        'material_weight' => 'decimal:4',
        'equipment_weight' => 'decimal:4',
        'fixed_weight' => 'decimal:4',
        'labor_index_base' => 'decimal:4',
        'labor_index_current' => 'decimal:4',
        'material_index_base' => 'decimal:4',
        'material_index_current' => 'decimal:4',
        'equipment_index_base' => 'decimal:4',
        'equipment_index_current' => 'decimal:4',
        'adjustment_factor' => 'decimal:6',
        'eligible_value' => 'decimal:3',
        'adjustment_amount' => 'decimal:3',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function interimPayment(): BelongsTo { return $this->belongsTo(InterimPayment::class); }
    public function progressCertificate(): BelongsTo { return $this->belongsTo(ProgressCertificate::class); }
}
