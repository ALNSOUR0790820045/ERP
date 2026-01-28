<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentMaintenance extends Model
{
    protected $table = 'equipment_maintenance';

    protected $fillable = [
        'equipment_id', 'maintenance_number', 'maintenance_type',
        'scheduled_date', 'actual_date', 'completion_date', 'description',
        'work_done', 'parts_replaced', 'labor_cost', 'parts_cost', 'total_cost',
        'status', 'service_provider', 'performed_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'actual_date' => 'date',
        'completion_date' => 'date',
        'labor_cost' => 'decimal:3',
        'parts_cost' => 'decimal:3',
        'total_cost' => 'decimal:3',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
