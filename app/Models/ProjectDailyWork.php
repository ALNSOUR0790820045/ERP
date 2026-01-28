<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDailyWork extends Model
{
    use HasFactory;

    protected $table = 'project_daily_works';

    protected $fillable = [
        'daily_report_id',
        'wbs_id',
        'contract_item_id',
        'description',
        'location',
        'quantity',
        'unit_id',
        'progress_percentage',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'progress_percentage' => 'decimal:2',
    ];

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(ProjectDailyReport::class, 'daily_report_id');
    }

    public function wbs(): BelongsTo
    {
        return $this->belongsTo(ProjectWbs::class, 'wbs_id');
    }

    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
