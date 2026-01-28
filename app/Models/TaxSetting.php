<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'tax_type', 'rate', 'calculation_method', 'applies_to',
        'effective_from', 'effective_to', 'account_id', 'is_active', 'notes',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeVat($query) { return $query->where('tax_type', 'vat'); }
    public function scopeWithholding($query) { return $query->where('tax_type', 'withholding'); }
}
