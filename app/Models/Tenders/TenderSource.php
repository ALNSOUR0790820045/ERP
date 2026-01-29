<?php

namespace App\Models\Tenders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * مصادر رصد المناقصات
 * Tender Discovery Sources
 */
class TenderSource extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name_ar',
        'name_en',
        'source_type',
        'url',
        'contact_person',
        'contact_phone',
        'contact_email',
        'description',
        'is_active',
        'requires_subscription',
        'subscription_cost',
        'subscription_period',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_subscription' => 'boolean',
        'subscription_cost' => 'decimal:2',
    ];

    // أنواع المصادر
    public const SOURCE_TYPES = [
        'newspaper' => 'جريدة',
        'website' => 'موقع إلكتروني',
        'government_portal' => 'بوابة حكومية',
        'direct_invitation' => 'دعوة مباشرة',
        'personal_relation' => 'علاقة شخصية',
        'tender_agency' => 'وكالة مناقصات',
        'social_media' => 'وسائل التواصل',
        'exhibition' => 'معرض',
        'other' => 'أخرى',
    ];

    // العلاقات
    public function discoveries(): HasMany
    {
        return $this->hasMany(TenderDiscovery::class, 'source_id');
    }

    public function tenders(): HasMany
    {
        return $this->hasMany(Tender::class, 'source_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('source_type', $type);
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?? $this->name_ar);
    }

    public function getSourceTypeLabelAttribute(): string
    {
        return self::SOURCE_TYPES[$this->source_type] ?? $this->source_type;
    }
}
