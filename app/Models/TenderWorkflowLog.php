<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سجل سير العمل للعطاء
 * Tender Workflow Log - تتبع مراحل العطاء
 */
class TenderWorkflowLog extends Model
{
    protected $fillable = [
        'tender_id',
        'from_status',
        'to_status',
        'transition_at',
        'notes',
        'performed_by',
    ];

    protected $casts = [
        'transition_at' => 'datetime',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * مدة البقاء في الحالة السابقة
     */
    public function getDurationFromPreviousAttribute(): ?string
    {
        if (!$this->from_status) {
            return null;
        }
        
        // البحث عن السجل السابق
        $previousLog = static::where('tender_id', $this->tender_id)
            ->where('to_status', $this->from_status)
            ->latest('transition_at')
            ->first();
        
        if (!$previousLog) {
            return null;
        }
        
        return $previousLog->transition_at->diffForHumans($this->transition_at, true);
    }
}
