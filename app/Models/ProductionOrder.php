<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'order_date',
        'bom_id',
        'project_id',
        'planned_quantity',
        'produced_quantity',
        'rejected_quantity',
        'planned_start',
        'planned_end',
        'actual_start',
        'actual_end',
        'priority',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'planned_start' => 'datetime',
        'planned_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'planned_quantity' => 'decimal:3',
        'produced_quantity' => 'decimal:3',
        'rejected_quantity' => 'decimal:3',
    ];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class);
    }

    public function concreteBatches(): HasMany
    {
        return $this->hasMany(ConcreteBatch::class);
    }

    public function dailyLogs(): HasMany
    {
        return $this->hasMany(DailyProductionLog::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCompletionPercentageAttribute(): float
    {
        if ($this->planned_quantity <= 0) return 0;
        return round(($this->produced_quantity / $this->planned_quantity) * 100, 2);
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::whereYear('created_at', $year)->count() + 1;
        return "PO-{$year}-" . str_pad($last, 5, '0', STR_PAD_LEFT);
    }
}
