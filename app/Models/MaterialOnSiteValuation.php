<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialOnSiteValuation extends Model
{
    use HasFactory;

    protected $fillable = [
        'interim_payment_id', 'progress_certificate_id', 'material_id',
        'description', 'quantity', 'unit', 'unit_rate', 'amount',
        'previous_amount', 'current_amount', 'location_on_site',
        'delivery_date', 'delivery_note', 'inspection_status',
        'notes',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'quantity' => 'decimal:4',
        'unit_rate' => 'decimal:4',
        'amount' => 'decimal:3',
        'previous_amount' => 'decimal:3',
        'current_amount' => 'decimal:3',
    ];

    public function interimPayment(): BelongsTo { return $this->belongsTo(InterimPayment::class); }
    public function progressCertificate(): BelongsTo { return $this->belongsTo(ProgressCertificate::class); }
    public function material(): BelongsTo { return $this->belongsTo(Material::class); }

    public function scopeApproved($query) { return $query->where('inspection_status', 'approved'); }
}
