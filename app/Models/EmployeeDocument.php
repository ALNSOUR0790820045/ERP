<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'document_type', 'document_number', 'title',
        'issue_date', 'expiry_date', 'issuing_authority', 'file_path',
        'file_name', 'file_size', 'mime_type',
        'is_verified', 'verified_by', 'verified_at', 'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
        'is_verified' => 'boolean',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }

    public function scopeExpiringSoon($query, int $days = 30) {
        return $query->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }
    public function scopeExpired($query) {
        return $query->whereNotNull('expiry_date')->where('expiry_date', '<', now());
    }
}
