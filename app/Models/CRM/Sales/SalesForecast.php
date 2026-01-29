<?php

namespace App\Models\CRM\Sales;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesForecast extends Model
{
    protected $fillable = [
        'forecast_code',
        'user_id',
        'team_id',
        'year',
        'quarter',
        'month',
        'target_amount',
        'pipeline_value',
        'weighted_value',
        'closed_value',
        'gap_amount',
        'achievement_percentage',
        'status',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'quarter' => 'integer',
        'month' => 'integer',
        'target_amount' => 'decimal:2',
        'pipeline_value' => 'decimal:2',
        'weighted_value' => 'decimal:2',
        'closed_value' => 'decimal:2',
        'gap_amount' => 'decimal:2',
        'achievement_percentage' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Boot
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($forecast) {
            if (empty($forecast->forecast_code)) {
                $forecast->forecast_code = 'FC-' . $forecast->year . '-' . str_pad($forecast->month ?? $forecast->quarter, 2, '0', STR_PAD_LEFT);
            }
        });
    }

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Methods
    public function recalculate(): void
    {
        $startDate = now()->setYear($this->year)->setMonth($this->month ?? 1)->startOfMonth();
        $endDate = $this->month 
            ? $startDate->copy()->endOfMonth() 
            : $startDate->copy()->addMonths(3)->endOfMonth();

        $deals = PipelineDeal::where('assigned_to', $this->user_id)
            ->where('expected_close_date', '>=', $startDate)
            ->where('expected_close_date', '<=', $endDate)
            ->get();

        $this->pipeline_value = $deals->where('status', 'open')->sum('deal_value');
        $this->weighted_value = $deals->where('status', 'open')->sum('weighted_value');
        $this->closed_value = $deals->where('status', 'won')->sum('deal_value');
        $this->gap_amount = $this->target_amount - $this->closed_value;
        $this->achievement_percentage = $this->target_amount > 0 
            ? ($this->closed_value / $this->target_amount) * 100 
            : 0;

        $this->save();
    }

    public function approve(int $approverId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);
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
