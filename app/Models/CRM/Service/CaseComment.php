<?php

namespace App\Models\CRM\Service;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseComment extends Model
{
    protected $fillable = [
        'case_id',
        'comment',
        'comment_type',
        'is_resolution',
        'created_by',
    ];

    protected $casts = [
        'is_resolution' => 'boolean',
    ];

    // العلاقات
    public function case(): BelongsTo
    {
        return $this->belongsTo(ServiceCase::class, 'case_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('comment_type', 'public');
    }

    public function scopeInternal($query)
    {
        return $query->where('comment_type', 'internal');
    }

    public function scopeResolutions($query)
    {
        return $query->where('is_resolution', true);
    }

    // Methods
    public function isPublic(): bool
    {
        return $this->comment_type === 'public';
    }

    public function isInternal(): bool
    {
        return $this->comment_type === 'internal';
    }

    public function isSystem(): bool
    {
        return $this->comment_type === 'system';
    }
}
