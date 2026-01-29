<?php

namespace App\Models\CRM\Sales;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesQuota extends Model
{
    protected $fillable = [
        'user_id',
        'territory_id',
        'team_id',
        'year',
        'quarter',
        'month',
        'quota_type',
        'quota_amount',
        'achieved_amount',
        'achievement_percentage',
        'status',
    ];

    protected $casts = [
        'year' => 'integer',
        'quarter' => 'integer',
        'month' => 'integer',
        'quota_amount' => 'decimal:2',
        'achieved_amount' => 'decimal:2',
        'achievement_percentage' => 'decimal:2',
    ];

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(SalesTerritory::class, 'territory_id');
    }

    // Scopes
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('quota_type', $type);
    }

    // Methods
    public function recalculate(): void
    {
        $startDate = now()->setYear($this->year)->setMonth($this->month ?? 1)->startOfMonth();
        $endDate = $this->month 
            ? $startDate->copy()->endOfMonth() 
            : $startDate->copy()->addMonths(2)->endOfMonth();

        $achieved = match($this->quota_type) {
            'revenue' => $this->calculateRevenueAchievement($startDate, $endDate),
            'deals' => $this->calculateDealsAchievement($startDate, $endDate),
            'new_customers' => $this->calculateNewCustomersAchievement($startDate, $endDate),
            default => 0,
        };

        $this->achieved_amount = $achieved;
        $this->achievement_percentage = $this->quota_amount > 0 
            ? ($achieved / $this->quota_amount) * 100 
            : 0;

        if ($this->achievement_percentage >= 100) {
            $this->status = 'achieved';
        }

        $this->save();
    }

    private function calculateRevenueAchievement($startDate, $endDate): float
    {
        return PipelineDeal::where('assigned_to', $this->user_id)
            ->where('status', 'won')
            ->whereBetween('actual_close_date', [$startDate, $endDate])
            ->sum('deal_value');
    }

    private function calculateDealsAchievement($startDate, $endDate): int
    {
        return PipelineDeal::where('assigned_to', $this->user_id)
            ->where('status', 'won')
            ->whereBetween('actual_close_date', [$startDate, $endDate])
            ->count();
    }

    private function calculateNewCustomersAchievement($startDate, $endDate): int
    {
        return PipelineDeal::where('assigned_to', $this->user_id)
            ->where('status', 'won')
            ->whereBetween('actual_close_date', [$startDate, $endDate])
            ->distinct('customer_id')
            ->count('customer_id');
    }

    public function getGapAmount(): float
    {
        return max(0, $this->quota_amount - $this->achieved_amount);
    }

    public function isOnTrack(): bool
    {
        $expectedProgress = $this->getExpectedProgress();
        return $this->achievement_percentage >= ($expectedProgress * 0.8);
    }

    private function getExpectedProgress(): float
    {
        if (!$this->month) return 100;
        
        $dayOfMonth = now()->day;
        $daysInMonth = now()->daysInMonth;
        
        return ($dayOfMonth / $daysInMonth) * 100;
    }
}
