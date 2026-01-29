<?php

namespace App\Models\CRM\Lead;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadAssignment extends Model
{
    protected $fillable = [
        'lead_id',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'assignment_reason',
        'status',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    // العلاقات
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    // Methods
    public function complete(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function reassign(int $newUserId, ?string $reason = null): LeadAssignment
    {
        $this->update(['status' => 'reassigned']);
        
        return static::create([
            'lead_id' => $this->lead_id,
            'assigned_to' => $newUserId,
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
            'assignment_reason' => $reason,
            'status' => 'active',
        ]);
    }
}
