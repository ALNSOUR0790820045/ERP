<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rfq extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rfq_number',
        'rfq_date',
        'subject',
        'rfq_type',
        'deadline',
        'validity_days',
        'delivery_required_date',
        'delivery_location',
        'payment_terms',
        'terms_conditions',
        'status',
        'project_id',
        'created_by',
    ];

    protected $casts = [
        'rfq_date' => 'date',
        'deadline' => 'datetime',
        'delivery_required_date' => 'date',
        'validity_days' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RfqItem::class);
    }

    public function rfqSuppliers(): HasMany
    {
        return $this->hasMany(RfqSupplier::class);
    }

    public function supplierQuotes(): HasMany
    {
        return $this->hasMany(SupplierQuote::class);
    }

    public function bidComparisons(): HasMany
    {
        return $this->hasMany(BidComparison::class);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->deadline < now();
    }

    public function getDaysRemainingAttribute(): int
    {
        return now()->diffInDays($this->deadline, false);
    }

    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            'draft' => 'مسودة',
            'sent' => 'مرسل',
            'closed' => 'مغلق',
            'awarded' => 'مُرسى',
            'cancelled' => 'ملغى',
            default => $this->status,
        };
    }

    public function getRfqTypeNameAttribute(): string
    {
        return match($this->rfq_type) {
            'rfq' => 'طلب عروض أسعار',
            'rfp' => 'طلب عروض فنية ومالية',
            'rfi' => 'طلب معلومات',
            default => $this->rfq_type,
        };
    }

    public static function generateNumber(): string
    {
        $year = now()->year;
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('RFQ-%d-%04d', $year, $count);
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!$model->rfq_number) {
                $model->rfq_number = static::generateNumber();
            }
        });
    }
}
