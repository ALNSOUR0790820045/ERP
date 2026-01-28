<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Allowance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'allowance_type', 'name', 'amount',
        'calculation_type', 'percentage', 'is_taxable', 'is_recurring',
        'effective_from', 'effective_to', 'notes', 'is_active',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'amount' => 'decimal:3',
        'percentage' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeRecurring($query) { return $query->where('is_recurring', true); }
}
