<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidComparison extends Model
{
    protected $fillable = [
        'rfq_id',
        'comparison_number',
        'comparison_date',
        'technical_evaluation',
        'commercial_evaluation',
        'recommendation',
        'recommended_supplier_id',
        'approved_by',
        'approved_date',
        'status',
    ];

    protected $casts = [
        'comparison_date' => 'date',
        'approved_date' => 'date',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function recommendedSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'recommended_supplier_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            'draft' => 'مسودة',
            'pending' => 'في انتظار الموافقة',
            'approved' => 'معتمد',
            'rejected' => 'مرفوض',
            default => $this->status,
        };
    }
}
