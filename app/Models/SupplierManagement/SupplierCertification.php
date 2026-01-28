<?php

namespace App\Models\SupplierManagement;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierCertification extends Model
{
    protected $fillable = [
        'supplier_id', 'certification_type', 'certification_number', 'certification_name',
        'issuing_authority', 'issuing_country', 'issue_date', 'expiry_date', 'status',
        'file_path', 'is_verified', 'verified_by', 'verified_at', 'notes', 'metadata',
    ];

    protected $casts = [
        'issue_date' => 'date', 'expiry_date' => 'date', 'verified_at' => 'datetime',
        'is_verified' => 'boolean', 'metadata' => 'array',
    ];

    const TYPE_ISO_9001 = 'iso_9001';
    const TYPE_ISO_14001 = 'iso_14001';
    const TYPE_ISO_45001 = 'iso_45001';
    const TYPE_OHSAS = 'ohsas';
    const TYPE_OTHER = 'other';

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function verifiedBy(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }

    public function scopeActive($q) { return $q->where('status', 'active'); }
    public function scopeExpiring($q, $days = 30) {
        return $q->whereNotNull('expiry_date')->where('expiry_date', '<=', now()->addDays($days))->where('expiry_date', '>', now());
    }
    public function scopeExpired($q) { return $q->whereNotNull('expiry_date')->where('expiry_date', '<', now()); }

    public function isExpired(): bool { return $this->expiry_date && $this->expiry_date->isPast(); }
    public function isExpiringSoon($days = 30): bool {
        return $this->expiry_date && $this->expiry_date->between(now(), now()->addDays($days));
    }

    public function verify(User $user): void {
        $this->update(['is_verified' => true, 'verified_by' => $user->id, 'verified_at' => now()]);
    }
}
