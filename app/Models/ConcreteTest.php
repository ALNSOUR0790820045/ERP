<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConcreteTest extends Model
{
    protected $fillable = [
        'test_number',
        'batch_id',
        'casting_date',
        'test_age',
        'test_date',
        'target_strength',
        'actual_strength',
        'result',
        'tested_by',
        'lab_name',
        'notes',
    ];

    protected $casts = [
        'casting_date' => 'date',
        'test_date' => 'date',
        'target_strength' => 'decimal:2',
        'actual_strength' => 'decimal:2',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ConcreteBatch::class, 'batch_id');
    }

    public function getStrengthRatioAttribute(): ?float
    {
        if (!$this->target_strength || $this->target_strength <= 0) return null;
        return round(($this->actual_strength / $this->target_strength) * 100, 2);
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::whereYear('created_at', $year)->count() + 1;
        return "CT-{$year}-" . str_pad($last, 5, '0', STR_PAD_LEFT);
    }
}
