<?php

namespace App\Models\Tenders;

use App\Models\Tender;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * رصد العطاء من المصدر
 * Tender Discovery Record
 */
class TenderDiscovery extends Model
{
    protected $fillable = [
        'tender_id',
        'source_id',
        'discovery_date',
        'discovered_by',
        'source_reference',
        'initial_notes',
        'priority',
        'is_verified',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'discovery_date' => 'date',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    // مستويات الأولوية
    public const PRIORITIES = [
        'low' => 'منخفضة',
        'medium' => 'متوسطة',
        'high' => 'عالية',
        'urgent' => 'عاجلة',
    ];

    // العلاقات
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(TenderSource::class);
    }

    public function discoverer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discovered_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    // Methods
    public function verify(User $user): void
    {
        $this->update([
            'is_verified' => true,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);
    }

    // Accessors
    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }
}
