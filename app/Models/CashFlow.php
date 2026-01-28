<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashFlow extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'company_id', 'fiscal_year', 'period_from', 'period_to',
        'operating_activities', 'investing_activities', 'financing_activities',
        'net_change', 'opening_cash', 'closing_cash',
        'status', 'generated_by', 'notes',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'operating_activities' => 'decimal:3',
        'investing_activities' => 'decimal:3',
        'financing_activities' => 'decimal:3',
        'net_change' => 'decimal:3',
        'opening_cash' => 'decimal:3',
        'closing_cash' => 'decimal:3',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function generator(): BelongsTo { return $this->belongsTo(User::class, 'generated_by'); }
    public function items(): HasMany { return $this->hasMany(CashFlowItem::class); }
}
