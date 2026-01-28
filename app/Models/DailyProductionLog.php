<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyProductionLog extends Model
{
    protected $fillable = [
        'workshop_id',
        'log_date',
        'shift',
        'production_order_id',
        'quantity_produced',
        'unit',
        'workers_count',
        'hours_worked',
        'activities',
        'issues',
        'recorded_by',
    ];

    protected $casts = [
        'log_date' => 'date',
        'quantity_produced' => 'decimal:3',
        'hours_worked' => 'decimal:2',
    ];

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getProductivityAttribute(): ?float
    {
        if (!$this->hours_worked || $this->hours_worked <= 0) return null;
        return round($this->quantity_produced / $this->hours_worked, 2);
    }
}
