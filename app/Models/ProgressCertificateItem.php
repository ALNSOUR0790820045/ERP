<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressCertificateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'progress_certificate_id',
        'boq_item_id',
        'item_code',
        'description',
        'unit',
        'contract_quantity',
        'contract_rate',
        'contract_amount',
        
        // الكميات التراكمية
        'cumulative_quantity',
        'previous_quantity',
        'current_quantity',
        
        // المبالغ
        'cumulative_amount',
        'previous_amount',
        'current_amount',
        
        // نسبة الإنجاز
        'completion_percentage',
        
        'notes',
    ];

    protected $casts = [
        'contract_quantity' => 'decimal:3',
        'contract_rate' => 'decimal:3',
        'contract_amount' => 'decimal:3',
        'cumulative_quantity' => 'decimal:3',
        'previous_quantity' => 'decimal:3',
        'current_quantity' => 'decimal:3',
        'cumulative_amount' => 'decimal:3',
        'previous_amount' => 'decimal:3',
        'current_amount' => 'decimal:3',
        'completion_percentage' => 'decimal:2',
    ];

    // العلاقات
    public function progressCertificate(): BelongsTo
    {
        return $this->belongsTo(ProgressCertificate::class);
    }

    public function boqItem(): BelongsTo
    {
        return $this->belongsTo(BOQItem::class, 'boq_item_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // حساب الكمية الحالية
            $item->current_quantity = $item->cumulative_quantity - $item->previous_quantity;
            
            // حساب المبالغ
            $item->cumulative_amount = $item->cumulative_quantity * $item->contract_rate;
            $item->previous_amount = $item->previous_quantity * $item->contract_rate;
            $item->current_amount = $item->current_quantity * $item->contract_rate;
            
            // نسبة الإنجاز
            if ($item->contract_quantity > 0) {
                $item->completion_percentage = ($item->cumulative_quantity / $item->contract_quantity) * 100;
            }
        });
    }
}
