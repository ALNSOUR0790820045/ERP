<?php

namespace App\Models\CRM\Lead;

use App\Models\User;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\CustomerContact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadConversion extends Model
{
    protected $fillable = [
        'lead_id',
        'customer_id',
        'opportunity_id',
        'contact_id',
        'converted_at',
        'converted_by',
        'lead_value',
        'days_to_convert',
        'notes',
    ];

    protected $casts = [
        'converted_at' => 'datetime',
        'lead_value' => 'decimal:2',
        'days_to_convert' => 'integer',
    ];

    // العلاقات
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CustomerContact::class, 'contact_id');
    }

    public function converter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    // Scopes
    public function scopeWithinPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('converted_at', [$startDate, $endDate]);
    }

    // Methods
    public static function getAverageConversionDays(): float
    {
        return static::whereNotNull('days_to_convert')->avg('days_to_convert') ?? 0;
    }

    public static function getTotalConvertedValue($startDate = null, $endDate = null): float
    {
        $query = static::query();
        
        if ($startDate && $endDate) {
            $query->withinPeriod($startDate, $endDate);
        }
        
        return $query->sum('lead_value') ?? 0;
    }
}
