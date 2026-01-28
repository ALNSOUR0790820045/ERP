<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashBox extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'currency_id', 'location', 'custodian_id',
        'opening_balance', 'current_balance', 'max_balance',
        'is_active', 'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:3',
        'current_balance' => 'decimal:3',
        'max_balance' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function custodian(): BelongsTo { return $this->belongsTo(User::class, 'custodian_id'); }
    public function transactions(): HasMany { return $this->hasMany(CashTransaction::class); }

    public function scopeActive($query) { return $query->where('is_active', true); }
}
