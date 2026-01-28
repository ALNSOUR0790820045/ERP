<?php

namespace App\Models\SupplierManagement;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierLicense extends Model
{
    protected $fillable = [
        'supplier_id', 'license_type', 'license_number', 'license_name',
        'issuing_authority', 'jurisdiction', 'issue_date', 'expiry_date', 'status',
        'file_path', 'is_verified', 'verified_by', 'renewal_reminder_days', 'notes', 'metadata',
    ];

    protected $casts = [
        'issue_date' => 'date', 'expiry_date' => 'date',
        'is_verified' => 'boolean', 'metadata' => 'array',
    ];

    const TYPE_COMMERCIAL = 'commercial';
    const TYPE_TRADE = 'trade';
    const TYPE_PROFESSIONAL = 'professional';
    const TYPE_IMPORT_EXPORT = 'import_export';
    const TYPE_SAFETY = 'safety';

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function verifiedBy(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }

    public function scopeActive($q) { return $q->where('status', 'active'); }
    public function scopeExpiring($q, $days = 30) {
        return $q->where('expiry_date', '<=', now()->addDays($days))->where('expiry_date', '>', now());
    }

    public function isExpired(): bool { return $this->expiry_date->isPast(); }
    public function daysUntilExpiry(): int { return now()->diffInDays($this->expiry_date, false); }
    public function needsRenewalReminder(): bool { return $this->daysUntilExpiry() <= $this->renewal_reminder_days; }
}
