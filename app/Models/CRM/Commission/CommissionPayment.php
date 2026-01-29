<?php

namespace App\Models\CRM\Commission;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionPayment extends Model
{
    protected $fillable = [
        'payment_number',
        'calculation_id',
        'user_id',
        'amount',
        'currency',
        'payment_date',
        'payment_method',
        'reference_number',
        'status',
        'processed_by',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'processed_at' => 'datetime',
    ];

    // Boot
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = 'CP-' . date('Ymd') . '-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    // العلاقات
    public function calculation(): BelongsTo
    {
        return $this->belongsTo(CommissionCalculation::class, 'calculation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    // Methods
    public function process(int $processedBy, ?string $referenceNumber = null): void
    {
        $this->update([
            'status' => 'processed',
            'processed_by' => $processedBy,
            'processed_at' => now(),
            'reference_number' => $referenceNumber,
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => 'paid']);
        $this->calculation?->markAsPaid();
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    // إحصائيات
    public static function getTotalPaidForUser(int $userId, int $year): float
    {
        return static::forUser($userId)
            ->paid()
            ->whereYear('payment_date', $year)
            ->sum('amount');
    }

    public static function getTotalPaidForPeriod($startDate, $endDate): float
    {
        return static::paid()
            ->inPeriod($startDate, $endDate)
            ->sum('amount');
    }

    public static function getPendingTotal(): float
    {
        return static::pending()->sum('amount');
    }
}
