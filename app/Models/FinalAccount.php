<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinalAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'project_id', 'account_number', 'account_date',
        'original_contract_value', 'variations_value', 'revised_contract_value',
        'final_measured_value', 'claims_value', 'total_value',
        'total_certified', 'retention_held', 'retention_released',
        'advance_issued', 'advance_recovered', 'liquidated_damages',
        'other_deductions', 'final_payable', 'currency_id',
        'status', 'prepared_by', 'reviewed_by', 'approved_by',
        'approved_at', 'notes',
    ];

    protected $casts = [
        'account_date' => 'date',
        'approved_at' => 'datetime',
        'original_contract_value' => 'decimal:3',
        'variations_value' => 'decimal:3',
        'revised_contract_value' => 'decimal:3',
        'final_measured_value' => 'decimal:3',
        'claims_value' => 'decimal:3',
        'total_value' => 'decimal:3',
        'total_certified' => 'decimal:3',
        'retention_held' => 'decimal:3',
        'retention_released' => 'decimal:3',
        'advance_issued' => 'decimal:3',
        'advance_recovered' => 'decimal:3',
        'liquidated_damages' => 'decimal:3',
        'other_deductions' => 'decimal:3',
        'final_payable' => 'decimal:3',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function preparer(): BelongsTo { return $this->belongsTo(User::class, 'prepared_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany { return $this->hasMany(FinalAccountItem::class); }

    public function scopeApproved($query) { return $query->where('status', 'approved'); }
}
