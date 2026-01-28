<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAssetMaintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixed_asset_id',
        'maintenance_date',
        'maintenance_type',
        'description',
        'cost',
        'performed_by',
        'vendor_name',
        'next_maintenance_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'cost' => 'decimal:3',
        'next_maintenance_date' => 'date',
    ];

    // العلاقات
    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    // الثوابت
    public const TYPES = [
        'preventive' => 'صيانة وقائية',
        'corrective' => 'صيانة تصحيحية',
        'upgrade' => 'ترقية',
    ];

    public const STATUSES = [
        'scheduled' => 'مجدولة',
        'in_progress' => 'قيد التنفيذ',
        'completed' => 'مكتملة',
    ];
}
