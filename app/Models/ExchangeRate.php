<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_currency_id', 'to_currency_id', 'rate', 'inverse_rate',
        'rate_date', 'source', 'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'inverse_rate' => 'decimal:6',
        'rate_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function fromCurrency(): BelongsTo { return $this->belongsTo(Currency::class, 'from_currency_id'); }
    public function toCurrency(): BelongsTo { return $this->belongsTo(Currency::class, 'to_currency_id'); }
    public function history(): HasMany { return $this->hasMany(ExchangeRateHistory::class); }

    public function scopeActive($query) { return $query->where('is_active', true); }

    public static function getRate(int $fromCurrencyId, int $toCurrencyId, ?string $date = null): ?float
    {
        return static::where('from_currency_id', $fromCurrencyId)
            ->where('to_currency_id', $toCurrencyId)
            ->where('is_active', true)
            ->when($date, fn($q) => $q->where('rate_date', '<=', $date))
            ->orderBy('rate_date', 'desc')
            ->value('rate');
    }
}
