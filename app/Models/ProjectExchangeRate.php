<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectExchangeRate extends Model
{
    use HasFactory;

    protected $table = 'project_exchange_rates';

    protected $fillable = [
        'project_id',
        'from_currency',
        'to_currency',
        'exchange_rate',
        'effective_date',
        'expiry_date',
        'rate_type',
        'source',
        'is_active',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBudgetRate($query)
    {
        return $query->where('rate_type', 'budget');
    }

    public function scopeForecastRate($query)
    {
        return $query->where('rate_type', 'forecast');
    }

    public function scopeActualRate($query)
    {
        return $query->where('rate_type', 'actual');
    }

    public function scopeHedgedRate($query)
    {
        return $query->where('rate_type', 'hedged');
    }

    public function scopeForCurrencyPair($query, string $from, string $to)
    {
        return $query->where('from_currency', $from)->where('to_currency', $to);
    }

    public function scopeEffectiveOn($query, $date)
    {
        return $query->where('effective_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', $date);
            });
    }

    // Methods
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getRateTypeArabicAttribute(): string
    {
        $types = [
            'budget' => 'موازنة',
            'forecast' => 'توقعي',
            'actual' => 'فعلي',
            'hedged' => 'تحوط',
        ];

        return $types[$this->rate_type] ?? $this->rate_type;
    }

    public function getCurrencyPairAttribute(): string
    {
        return $this->from_currency . '/' . $this->to_currency;
    }

    public function convert(float $amount): float
    {
        return $amount * $this->exchange_rate;
    }

    public function reverseConvert(float $amount): float
    {
        if ($this->exchange_rate == 0) {
            return 0;
        }
        return $amount / $this->exchange_rate;
    }

    /**
     * Get the current effective rate for a currency pair
     */
    public static function getCurrentRate(int $projectId, string $from, string $to, ?string $rateType = null): ?self
    {
        $query = self::where('project_id', $projectId)
            ->forCurrencyPair($from, $to)
            ->active()
            ->effectiveOn(now());

        if ($rateType) {
            $query->where('rate_type', $rateType);
        }

        return $query->orderBy('effective_date', 'desc')->first();
    }

    /**
     * Get historical rates for a currency pair
     */
    public static function getHistoricalRates(int $projectId, string $from, string $to, int $limit = 12): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('project_id', $projectId)
            ->forCurrencyPair($from, $to)
            ->orderBy('effective_date', 'desc')
            ->limit($limit)
            ->get();
    }
}
