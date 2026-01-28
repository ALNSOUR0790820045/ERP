<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDailyMaterial extends Model
{
    use HasFactory;

    protected $table = 'project_daily_materials';

    protected $fillable = [
        'daily_report_id',
        'material_name',
        'material_code',
        'quantity',
        'unit_id',
        'supplier',
        'delivery_note',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(ProjectDailyReport::class, 'daily_report_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
