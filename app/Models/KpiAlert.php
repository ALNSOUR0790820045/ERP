<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiAlert extends Model
{
    protected $fillable = [
        'kpi_id',
        'kpi_value_id',
        'alert_type',
        'title',
        'message',
        'threshold_value',
        'actual_value',
        'is_read',
        'is_acknowledged',
        'acknowledged_by',
        'acknowledged_at',
    ];

    protected $casts = [
        'threshold_value' => 'decimal:4',
        'actual_value' => 'decimal:4',
        'is_read' => 'boolean',
        'is_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
    ];

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class);
    }

    public function kpiValue(): BelongsTo
    {
        return $this->belongsTo(KpiValue::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function acknowledge(int $userId): void
    {
        $this->update([
            'is_acknowledged' => true,
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
        ]);
    }
}
