<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ملف الائتمان للعميل
 * Customer Credit Profile
 */
class CustomerCreditProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'credit_limit',
        'current_balance',
        'available_credit',
        'overdue_amount',
        'credit_rating',
        'credit_score',
        'last_score_update',
        'payment_terms_days',
        'early_payment_discount',
        'discount_days',
        'credit_status',
        'hold_reason',
        'held_by',
        'held_at',
        'warning_threshold_percentage',
        'notify_on_threshold',
        'notify_on_overdue',
        'auto_hold_on_limit',
        'total_invoices',
        'paid_on_time',
        'paid_late',
        'average_days_to_pay',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'available_credit' => 'decimal:2',
        'overdue_amount' => 'decimal:2',
        'credit_score' => 'integer',
        'last_score_update' => 'date',
        'payment_terms_days' => 'integer',
        'early_payment_discount' => 'decimal:2',
        'discount_days' => 'integer',
        'held_at' => 'datetime',
        'warning_threshold_percentage' => 'decimal:2',
        'notify_on_threshold' => 'boolean',
        'notify_on_overdue' => 'boolean',
        'auto_hold_on_limit' => 'boolean',
        'total_invoices' => 'integer',
        'paid_on_time' => 'integer',
        'paid_late' => 'integer',
        'average_days_to_pay' => 'decimal:2',
    ];

    // Rating colors for UI
    public static array $ratingColors = [
        'A' => 'success',
        'B' => 'info',
        'C' => 'warning',
        'D' => 'danger',
        'F' => 'gray',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function heldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'held_by');
    }

    public function creditLimitChanges(): HasMany
    {
        return $this->hasMany(CreditLimitChange::class);
    }

    public function creditReviews(): HasMany
    {
        return $this->hasMany(CreditReview::class);
    }

    public function creditAlerts(): HasMany
    {
        return $this->hasMany(CreditAlert::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('credit_status', 'active');
    }

    public function scopeOnHold($query)
    {
        return $query->where('credit_status', 'on_hold');
    }

    public function scopeBlocked($query)
    {
        return $query->where('credit_status', 'blocked');
    }

    public function scopeNearLimit($query, float $percentage = 80)
    {
        return $query->whereRaw('(current_balance / credit_limit * 100) >= ?', [$percentage]);
    }

    // Methods
    public function updateBalance(): void
    {
        $this->available_credit = max(0, $this->credit_limit - $this->current_balance);
        $this->save();
        
        $this->checkThreshold();
    }

    public function checkThreshold(): void
    {
        if (!$this->notify_on_threshold) return;
        
        $usagePercentage = $this->credit_limit > 0 
            ? ($this->current_balance / $this->credit_limit * 100) 
            : 0;
        
        if ($usagePercentage >= $this->warning_threshold_percentage) {
            $this->createAlert('threshold_warning', 
                "اقترب العميل من حد الائتمان ({$usagePercentage}%)",
                $this->current_balance
            );
        }
        
        if ($usagePercentage >= 100 && $this->auto_hold_on_limit) {
            $this->holdCredit('تجاوز حد الائتمان تلقائياً');
        }
    }

    public function holdCredit(string $reason, ?int $userId = null): bool
    {
        $this->credit_status = 'on_hold';
        $this->hold_reason = $reason;
        $this->held_by = $userId;
        $this->held_at = now();
        
        $this->createAlert('limit_exceeded', $reason, $this->current_balance);
        
        return $this->save();
    }

    public function releaseHold(?int $userId = null): bool
    {
        $this->credit_status = 'active';
        $this->hold_reason = null;
        $this->held_by = null;
        $this->held_at = null;
        
        return $this->save();
    }

    public function blockCredit(string $reason, int $userId): bool
    {
        $this->credit_status = 'blocked';
        $this->hold_reason = $reason;
        $this->held_by = $userId;
        $this->held_at = now();
        
        return $this->save();
    }

    public function updateCreditLimit(float $newLimit, string $reason, int $changedBy, ?int $approvedBy = null): void
    {
        $previousLimit = $this->credit_limit;
        
        $this->creditLimitChanges()->create([
            'previous_limit' => $previousLimit,
            'new_limit' => $newLimit,
            'change_reason' => $reason,
            'change_type' => $newLimit > $previousLimit ? 'increase' : 'decrease',
            'changed_by' => $changedBy,
            'approved_by' => $approvedBy,
            'approved_at' => $approvedBy ? now() : null,
        ]);
        
        $this->credit_limit = $newLimit;
        $this->updateBalance();
    }

    public function calculateCreditScore(): int
    {
        $score = 500; // Base score
        
        // Payment history (40%)
        if ($this->total_invoices > 0) {
            $onTimeRate = $this->paid_on_time / $this->total_invoices;
            $score += (int)($onTimeRate * 200);
        }
        
        // Credit utilization (30%)
        if ($this->credit_limit > 0) {
            $utilization = $this->current_balance / $this->credit_limit;
            if ($utilization < 0.3) $score += 150;
            elseif ($utilization < 0.5) $score += 100;
            elseif ($utilization < 0.7) $score += 50;
            elseif ($utilization > 0.9) $score -= 50;
        }
        
        // Average days to pay (20%)
        if ($this->average_days_to_pay <= $this->payment_terms_days) {
            $score += 100;
        } elseif ($this->average_days_to_pay <= $this->payment_terms_days * 1.5) {
            $score += 50;
        } else {
            $score -= 50;
        }
        
        // Overdue amount (10%)
        if ($this->overdue_amount == 0) {
            $score += 50;
        } elseif ($this->overdue_amount < $this->credit_limit * 0.1) {
            $score += 25;
        } else {
            $score -= 50;
        }
        
        return max(0, min(850, $score));
    }

    public function updateCreditRating(): void
    {
        $this->credit_score = $this->calculateCreditScore();
        $this->last_score_update = now();
        
        if ($this->credit_score >= 750) $this->credit_rating = 'A';
        elseif ($this->credit_score >= 650) $this->credit_rating = 'B';
        elseif ($this->credit_score >= 550) $this->credit_rating = 'C';
        elseif ($this->credit_score >= 400) $this->credit_rating = 'D';
        else $this->credit_rating = 'F';
        
        $this->save();
    }

    public function createAlert(string $type, string $message, ?float $amount = null): CreditAlert
    {
        return $this->creditAlerts()->create([
            'alert_type' => $type,
            'alert_message' => $message,
            'related_amount' => $amount,
        ]);
    }

    public function canPlaceOrder(float $orderAmount): array
    {
        if ($this->credit_status === 'blocked') {
            return ['allowed' => false, 'reason' => 'العميل محظور من الطلبات'];
        }
        
        if ($this->credit_status === 'on_hold') {
            return ['allowed' => false, 'reason' => 'حساب العميل موقوف مؤقتاً: ' . $this->hold_reason];
        }
        
        if ($this->available_credit < $orderAmount) {
            return [
                'allowed' => false, 
                'reason' => "الرصيد المتاح ({$this->available_credit}) أقل من قيمة الطلب ({$orderAmount})"
            ];
        }
        
        return ['allowed' => true, 'reason' => null];
    }
}
