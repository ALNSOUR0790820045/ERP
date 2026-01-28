<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'approvable_type', 'approvable_id', 'approval_level', 'sequence',
        'approver_id', 'approver_role', 'status', 'approved_at',
        'comments', 'signature', 'ip_address',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function approvable()
    {
        return $this->morphTo();
    }

    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approver_id'); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopeRejected($query) { return $query->where('status', 'rejected'); }
}
