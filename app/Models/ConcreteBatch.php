<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConcreteBatch extends Model
{
    protected $fillable = [
        'batch_number',
        'production_date',
        'mix_design_id',
        'production_order_id',
        'project_id',
        'truck_number',
        'driver_name',
        'pour_location',
        'volume',
        'slump',
        'temperature',
        'samples_taken',
        'status',
        'notes',
        'produced_by',
    ];

    protected $casts = [
        'production_date' => 'datetime',
        'volume' => 'decimal:3',
        'slump' => 'decimal:2',
        'temperature' => 'decimal:2',
    ];

    public function mixDesign(): BelongsTo
    {
        return $this->belongsTo(ConcreteMixDesign::class, 'mix_design_id');
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(ConcreteTest::class, 'batch_id');
    }

    public function producedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'produced_by');
    }

    public static function generateNumber(): string
    {
        $today = date('Ymd');
        $last = static::whereDate('created_at', today())->count() + 1;
        return "CB-{$today}-" . str_pad($last, 4, '0', STR_PAD_LEFT);
    }
}
