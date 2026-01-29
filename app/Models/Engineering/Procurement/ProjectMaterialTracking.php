<?php

namespace App\Models\Engineering\Procurement;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMaterialTracking extends Model
{
    protected $table = 'project_material_tracking';

    protected $fillable = [
        'project_id',
        'purchase_order_id',
        'item_code',
        'description',
        'ordered_quantity',
        'received_quantity',
        'installed_quantity',
        'unit',
        'required_date',
        'promised_date',
        'actual_delivery_date',
        'delivery_location',
        'status',
        'notes',
    ];

    protected $casts = [
        'ordered_quantity' => 'decimal:4',
        'received_quantity' => 'decimal:4',
        'installed_quantity' => 'decimal:4',
        'required_date' => 'date',
        'promised_date' => 'date',
        'actual_delivery_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getRemainingQuantityAttribute(): float
    {
        return $this->ordered_quantity - $this->received_quantity;
    }

    public function getPendingInstallationAttribute(): float
    {
        return $this->received_quantity - $this->installed_quantity;
    }

    public function getDeliveryVarianceAttribute(): ?int
    {
        if (!$this->actual_delivery_date || !$this->promised_date) {
            return null;
        }
        return $this->actual_delivery_date->diffInDays($this->promised_date, false);
    }

    public function receive(float $quantity): void
    {
        $this->received_quantity += $quantity;
        
        if ($this->received_quantity >= $this->ordered_quantity) {
            $this->status = 'received';
            $this->actual_delivery_date = now();
        } else {
            $this->status = 'partially_received';
        }
        
        $this->save();
    }

    public function install(float $quantity): void
    {
        $this->installed_quantity += $quantity;
        
        if ($this->installed_quantity >= $this->received_quantity) {
            $this->status = 'installed';
        }
        
        $this->save();
    }

    public function scopeDelayed($query)
    {
        return $query->where('promised_date', '<', now())
            ->whereNotIn('status', ['received', 'installed']);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['ordered', 'in_transit', 'partially_received']);
    }

    public function scopeByProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}
