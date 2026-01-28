<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressCertificateMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'progress_certificate_id',
        'material_id',
        'material_code',
        'description',
        'unit',
        'quantity',
        'unit_price',
        'amount',
        'location',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:3',
        'amount' => 'decimal:3',
    ];

    // العلاقات
    public function progressCertificate(): BelongsTo
    {
        return $this->belongsTo(ProgressCertificate::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->amount = $item->quantity * $item->unit_price;
        });
    }
}
