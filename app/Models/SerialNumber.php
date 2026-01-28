<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerialNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id', 'serial_number', 'warehouse_id', 'supplier_id',
        'customer_id', 'project_id', 'receipt_date', 'warranty_start',
        'warranty_end', 'purchase_cost', 'status', 'notes',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'warranty_start' => 'date',
        'warranty_end' => 'date',
        'purchase_cost' => 'decimal:4',
    ];

    public function material(): BelongsTo { return $this->belongsTo(Material::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }

    public function scopeAvailable($query) { return $query->where('status', 'available'); }
    public function scopeIssued($query) { return $query->where('status', 'issued'); }
    public function scopeUnderWarranty($query) { 
        return $query->where('warranty_end', '>=', now()); 
    }
}
