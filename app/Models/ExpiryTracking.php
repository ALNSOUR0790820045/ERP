<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpiryTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id', 'warehouse_id', 'batch_number', 'quantity',
        'manufacture_date', 'expiry_date', 'alert_days_before',
        'status', 'alert_sent',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'quantity' => 'decimal:4',
        'alert_sent' => 'boolean',
    ];

    public function material(): BelongsTo { return $this->belongsTo(Material::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }

    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeExpiringSoon($query) {
        return $query->where('status', 'active')
            ->whereRaw('expiry_date <= DATE_ADD(NOW(), INTERVAL alert_days_before DAY)');
    }
    public function scopeExpired($query) { return $query->where('status', 'expired'); }
}
