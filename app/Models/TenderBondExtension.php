<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تمديدات الكفالات
 * Tender Bond Extensions
 */
class TenderBondExtension extends Model
{
    protected $fillable = [
        'bond_id',
        'extension_number',
        'request_date',
        'previous_expiry_date',
        'new_expiry_date',
        'extension_fee',
        'document_path',
        'reason',
        'requested_by',
    ];

    protected $casts = [
        'extension_number' => 'integer',
        'request_date' => 'date',
        'previous_expiry_date' => 'date',
        'new_expiry_date' => 'date',
        'extension_fee' => 'decimal:2',
    ];

    public function bond(): BelongsTo
    {
        return $this->belongsTo(TenderBond::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * مدة التمديد بالأيام
     */
    public function getExtensionDaysAttribute(): ?int
    {
        if (!$this->previous_expiry_date || !$this->new_expiry_date) {
            return null;
        }
        return $this->previous_expiry_date->diffInDays($this->new_expiry_date);
    }
}
