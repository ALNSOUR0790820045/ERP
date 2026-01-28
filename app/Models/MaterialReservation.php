<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_number', 'material_id', 'warehouse_id', 'project_id',
        'quantity', 'reservation_date', 'required_date', 'expiry_date',
        'purpose', 'status', 'reserved_by', 'fulfilled_by', 'fulfilled_at',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'required_date' => 'date',
        'expiry_date' => 'date',
        'fulfilled_at' => 'datetime',
        'quantity' => 'decimal:4',
    ];

    public function material(): BelongsTo { return $this->belongsTo(Material::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function reserver(): BelongsTo { return $this->belongsTo(User::class, 'reserved_by'); }
    public function fulfiller(): BelongsTo { return $this->belongsTo(User::class, 'fulfilled_by'); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeConfirmed($query) { return $query->where('status', 'confirmed'); }
    public function scopeExpired($query) { 
        return $query->where('status', 'pending')
            ->where('expiry_date', '<', now()); 
    }
}
