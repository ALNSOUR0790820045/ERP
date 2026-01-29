<?php

namespace App\Models\CRM\Engagement;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSegmentMember extends Model
{
    protected $fillable = [
        'segment_id',
        'customer_id',
        'added_at',
        'removed_at',
    ];

    protected $casts = [
        'added_at' => 'datetime',
        'removed_at' => 'datetime',
    ];

    // العلاقات
    public function segment(): BelongsTo
    {
        return $this->belongsTo(CustomerSegment::class, 'segment_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('removed_at');
    }

    public function scopeRemoved($query)
    {
        return $query->whereNotNull('removed_at');
    }

    // Methods
    public function isActive(): bool
    {
        return $this->removed_at === null;
    }

    public function remove(): void
    {
        $this->update(['removed_at' => now()]);
    }
}
