<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MultiCurrencyCost extends Model
{
    use HasFactory;

    protected $table = 'multi_currency_costs';

    protected $fillable = [
        'project_id',
        'gantt_task_id',
        'project_cost_id',
        'cost_type',
        'description',
        'original_currency',
        'original_amount',
        'exchange_rate',
        'exchange_rate_date',
        'base_currency',
        'base_amount',
        'exchange_rate_type',
        'hedge_rate',
        'variance_amount',
        'notes',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'exchange_rate_date' => 'date',
        'base_amount' => 'decimal:2',
        'hedge_rate' => 'decimal:6',
        'variance_amount' => 'decimal:2',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function ganttTask(): BelongsTo
    {
        return $this->belongsTo(GanttTask::class);
    }

    public function projectCost(): BelongsTo
    {
        return $this->belongsTo(ProjectCost::class);
    }

    // Scopes
    public function scopeLabor($query)
    {
        return $query->where('cost_type', 'labor');
    }

    public function scopeMaterial($query)
    {
        return $query->where('cost_type', 'material');
    }

    public function scopeEquipment($query)
    {
        return $query->where('cost_type', 'equipment');
    }

    public function scopeSubcontract($query)
    {
        return $query->where('cost_type', 'subcontract');
    }

    public function scopeByCurrency($query, string $currency)
    {
        return $query->where('original_currency', $currency);
    }

    public function scopeSpotRate($query)
    {
        return $query->where('exchange_rate_type', 'spot');
    }

    public function scopeHedged($query)
    {
        return $query->whereNotNull('hedge_rate');
    }

    // Methods
    public function convertToBaseCurrency(): void
    {
        $this->base_amount = $this->original_amount * $this->exchange_rate;
        $this->save();
    }

    public function calculateVariance(): void
    {
        if ($this->hedge_rate) {
            $hedgedAmount = $this->original_amount * $this->hedge_rate;
            $this->variance_amount = $this->base_amount - $hedgedAmount;
            $this->save();
        }
    }

    public function updateExchangeRate(float $newRate, ?string $rateType = null): void
    {
        $this->exchange_rate = $newRate;
        $this->exchange_rate_date = now();
        
        if ($rateType) {
            $this->exchange_rate_type = $rateType;
        }
        
        $this->convertToBaseCurrency();
        $this->calculateVariance();
    }

    public function getCostTypeArabicAttribute(): string
    {
        $types = [
            'labor' => 'عمالة',
            'material' => 'مواد',
            'equipment' => 'معدات',
            'subcontract' => 'مقاولات فرعية',
            'overhead' => 'مصاريف عامة',
            'contingency' => 'احتياطي',
            'other' => 'أخرى',
        ];

        return $types[$this->cost_type] ?? $this->cost_type;
    }

    public function getFormattedOriginalAmountAttribute(): string
    {
        return number_format($this->original_amount, 2) . ' ' . $this->original_currency;
    }

    public function getFormattedBaseAmountAttribute(): string
    {
        return number_format($this->base_amount, 2) . ' ' . $this->base_currency;
    }

    public function getVariancePercentAttribute(): ?float
    {
        if (!$this->variance_amount || !$this->base_amount || $this->base_amount == 0) {
            return null;
        }
        return round(($this->variance_amount / $this->base_amount) * 100, 2);
    }

    public static function getTotalByCurrency(int $projectId): array
    {
        return self::where('project_id', $projectId)
            ->selectRaw('original_currency, SUM(original_amount) as total_amount')
            ->groupBy('original_currency')
            ->pluck('total_amount', 'original_currency')
            ->toArray();
    }

    public static function getTotalInBaseCurrency(int $projectId): float
    {
        return self::where('project_id', $projectId)->sum('base_amount');
    }
}
