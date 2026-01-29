<?php

namespace App\Models\CRM\Commission;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CommissionCalculation extends Model
{
    protected $fillable = [
        'calculation_number',
        'user_id',
        'plan_id',
        'year',
        'month',
        'base_amount',
        'commission_amount',
        'accelerator_amount',
        'adjustment_amount',
        'adjustment_reason',
        'total_commission',
        'deals_count',
        'quota_achievement',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'base_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'accelerator_amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'deals_count' => 'integer',
        'quota_achievement' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Boot
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($calculation) {
            if (empty($calculation->calculation_number)) {
                $calculation->calculation_number = 'CC-' . $calculation->year . $calculation->month . '-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CommissionPlan::class, 'plan_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(CommissionPayment::class, 'calculation_id');
    }

    // Scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPeriod($query, int $year, ?int $month = null)
    {
        $query->where('year', $year);
        if ($month) {
            $query->where('month', $month);
        }
        return $query;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'calculated');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    // Methods
    public static function calculate(int $userId, int $year, int $month): self
    {
        // الحصول على خطة العمولة للمستخدم
        $userPlan = UserCommissionPlan::forUser($userId)
            ->active()
            ->first();
        
        if (!$userPlan) {
            throw new \Exception('No commission plan found for user');
        }
        
        $plan = $userPlan->plan;
        
        // حساب إجمالي المبيعات
        $deals = \App\Models\CRM\Sales\PipelineDeal::where('assigned_to', $userId)
            ->where('status', 'won')
            ->whereYear('actual_close_date', $year)
            ->whereMonth('actual_close_date', $month)
            ->get();
        
        $baseAmount = $deals->sum('deal_value');
        $dealsCount = $deals->count();
        
        // حساب نسبة تحقيق الحصة
        $quota = \App\Models\CRM\Sales\SalesQuota::forUser($userId)
            ->forMonth($year, $month)
            ->first();
        
        $quotaAchievement = $quota 
            ? ($baseAmount / $quota->quota_amount) * 100 
            : null;
        
        // حساب العمولة
        $commissionAmount = $plan->calculateCommission($baseAmount);
        
        // حساب المسرّع
        $acceleratorAmount = 0;
        if ($quotaAchievement) {
            $fullCommission = $plan->calculateWithAccelerator($baseAmount, $quotaAchievement);
            $acceleratorAmount = $fullCommission - $commissionAmount;
        }
        
        $totalCommission = $commissionAmount + $acceleratorAmount;
        
        // إنشاء أو تحديث الحساب
        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'year' => $year,
                'month' => $month,
            ],
            [
                'plan_id' => $plan->id,
                'base_amount' => $baseAmount,
                'commission_amount' => $commissionAmount,
                'accelerator_amount' => $acceleratorAmount,
                'total_commission' => $totalCommission,
                'deals_count' => $dealsCount,
                'quota_achievement' => $quotaAchievement,
                'status' => 'calculated',
            ]
        );
    }

    public function addAdjustment(float $amount, string $reason): void
    {
        $this->adjustment_amount = $amount;
        $this->adjustment_reason = $reason;
        $this->total_commission = $this->commission_amount + $this->accelerator_amount + $amount;
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

    public function dispute(): void
    {
        $this->update(['status' => 'disputed']);
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => 'paid']);
    }

    public function isPending(): bool
    {
        return $this->status === 'calculated';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function getPeriodLabel(): string
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];
        
        return ($months[$this->month] ?? '') . ' ' . $this->year;
    }
}
