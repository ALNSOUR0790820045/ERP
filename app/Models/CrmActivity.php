<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrmActivity extends Model
{
    protected $fillable = [
        'activity_type',
        'subject',
        'description',
        'related_type',
        'related_id',
        'activity_date',
        'due_date',
        'duration_minutes',
        'status',
        'priority',
        'outcome',
        'assigned_to',
        'created_by',
    ];

    protected $casts = [
        'activity_date' => 'datetime',
        'due_date' => 'datetime',
    ];

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeNameAttribute(): string
    {
        return match($this->activity_type) {
            'call' => 'مكالمة',
            'meeting' => 'اجتماع',
            'email' => 'بريد إلكتروني',
            'visit' => 'زيارة',
            'task' => 'مهمة',
            'note' => 'ملاحظة',
            default => $this->activity_type,
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'planned' && 
               $this->due_date && 
               $this->due_date->isPast();
    }
}
