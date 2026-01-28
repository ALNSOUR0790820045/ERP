<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrialBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'company_id', 'fiscal_year', 'as_of_date',
        'total_debit', 'total_credit', 'is_balanced',
        'status', 'generated_by', 'notes',
    ];

    protected $casts = [
        'as_of_date' => 'date',
        'total_debit' => 'decimal:3',
        'total_credit' => 'decimal:3',
        'is_balanced' => 'boolean',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function generator(): BelongsTo { return $this->belongsTo(User::class, 'generated_by'); }
    public function items(): HasMany { return $this->hasMany(TrialBalanceItem::class); }

    public function scopeBalanced($query) { return $query->where('is_balanced', true); }
}
