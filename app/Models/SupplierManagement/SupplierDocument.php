<?php

namespace App\Models\SupplierManagement;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierDocument extends Model
{
    protected $fillable = [
        'supplier_id', 'document_type', 'document_name', 'document_number',
        'file_path', 'file_name', 'file_size', 'mime_type', 'issue_date', 'expiry_date',
        'status', 'is_mandatory', 'is_verified', 'uploaded_by', 'verified_by', 'notes', 'metadata',
    ];

    protected $casts = [
        'issue_date' => 'date', 'expiry_date' => 'date',
        'is_mandatory' => 'boolean', 'is_verified' => 'boolean', 'metadata' => 'array',
    ];

    const TYPE_COMMERCIAL_REGISTER = 'commercial_register';
    const TYPE_TAX_CERTIFICATE = 'tax_certificate';
    const TYPE_INSURANCE = 'insurance';
    const TYPE_BANK_LETTER = 'bank_letter';
    const TYPE_FINANCIAL_STATEMENT = 'financial_statement';
    const TYPE_QUALITY_MANUAL = 'quality_manual';

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function uploadedBy(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function verifiedBy(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }

    public function scopeActive($q) { return $q->where('status', 'active'); }
    public function scopeMandatory($q) { return $q->where('is_mandatory', true); }
    public function scopeExpiring($q, $days = 30) {
        return $q->whereNotNull('expiry_date')->where('expiry_date', '<=', now()->addDays($days));
    }

    public function isExpired(): bool { return $this->expiry_date && $this->expiry_date->isPast(); }
    public function getFileSizeFormatted(): string {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' bytes';
    }
}
