<?php

namespace App\Models;

use App\Enums\SupplierType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name_ar', 'name_en', 'supplier_type', 'tax_number', 'commercial_register',
        'country_id', 'city_id', 'address', 'postal_code',
        'phone', 'mobile', 'fax', 'email', 'website',
        'contact_person', 'contact_phone', 'contact_email',
        'bank_name', 'bank_branch', 'bank_account', 'iban', 'swift_code',
        'payment_terms_days', 'credit_limit', 'balance', 'currency_id',
        'rating', 'is_approved', 'is_active', 'is_blacklisted', 'blacklist_reason',
        'notes', 'created_by',
    ];

    protected $casts = [
        'supplier_type' => SupplierType::class,
        'credit_limit' => 'decimal:3',
        'balance' => 'decimal:3',
        'rating' => 'decimal:2',
        'is_approved' => 'boolean',
        'is_active' => 'boolean',
        'is_blacklisted' => 'boolean',
    ];

    public function country(): BelongsTo { return $this->belongsTo(Country::class); }
    public function city(): BelongsTo { return $this->belongsTo(City::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function specializations(): BelongsToMany { return $this->belongsToMany(Specialization::class, 'supplier_specializations'); }
    public function evaluations(): HasMany { return $this->hasMany(SupplierEvaluation::class); }
    public function purchaseOrders(): HasMany { return $this->hasMany(PurchaseOrder::class); }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?? $this->name_ar);
    }

    public function getAverageRatingAttribute(): float
    {
        return $this->evaluations()->avg('overall_score') ?? 0;
    }
}
