<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_code',
        'company_name',
        'company_name_en',
        'customer_type',
        'industry',
        'classification',
        'tax_number',
        'commercial_reg',
        'address',
        'city',
        'country',
        'postal_code',
        'phone',
        'fax',
        'email',
        'website',
        'credit_limit',
        'payment_terms_days',
        'currency',
        'bank_name',
        'bank_account',
        'iban',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function primaryContact()
    {
        return $this->contacts()->where('is_primary', true)->first();
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(CustomerEvaluation::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class);
    }

    public function activities()
    {
        return $this->morphMany(CrmActivity::class, 'related');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeNameAttribute(): string
    {
        return match($this->customer_type) {
            'government' => 'حكومي',
            'semi_government' => 'شبه حكومي',
            'private' => 'قطاع خاص',
            'international' => 'دولي',
            'individual' => 'أفراد',
            default => $this->customer_type,
        };
    }

    public static function generateCode(): string
    {
        $last = static::withTrashed()->count() + 1;
        return "CUS-" . str_pad($last, 5, '0', STR_PAD_LEFT);
    }
}
