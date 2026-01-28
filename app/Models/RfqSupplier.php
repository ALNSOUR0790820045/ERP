<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqSupplier extends Model
{
    protected $fillable = [
        'rfq_id',
        'supplier_id',
        'sent_at',
        'responded_at',
        'response_status',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function getResponseStatusNameAttribute(): string
    {
        return match($this->response_status) {
            'pending' => 'في انتظار الرد',
            'received' => 'تم الاستلام',
            'declined' => 'اعتذر',
            'no_response' => 'لم يرد',
            default => $this->response_status,
        };
    }
}
