<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SteelFabricationOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'order_date',
        'project_id',
        'element_type',
        'drawing_number',
        'total_weight',
        'required_date',
        'delivery_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'required_date' => 'date',
        'delivery_date' => 'date',
        'total_weight' => 'decimal:3',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function barSchedules(): HasMany
    {
        return $this->hasMany(BarSchedule::class, 'steel_order_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function calculateTotalWeight(): void
    {
        $this->total_weight = $this->barSchedules()->sum('total_weight');
        $this->save();
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::whereYear('created_at', $year)->count() + 1;
        return "SF-{$year}-" . str_pad($last, 5, '0', STR_PAD_LEFT);
    }
}
