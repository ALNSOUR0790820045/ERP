<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PricingPrediction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pricing_model_id',
        'reference_type', // material, labor, equipment, boq_item
        'reference_id',
        'input_features',
        'predicted_price',
        'actual_price',
        'confidence_score',
        'prediction_range_min',
        'prediction_range_max',
        'variance',
        'variance_percent',
        'is_accurate',
        'feedback_status', // pending, confirmed, rejected
        'feedback_notes',
        'feedback_by',
        'feedback_at',
        'used_in_quotation',
        'quotation_id',
        'prediction_context',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'input_features' => 'array',
        'prediction_context' => 'array',
        'metadata' => 'array',
        'predicted_price' => 'decimal:2',
        'actual_price' => 'decimal:2',
        'confidence_score' => 'decimal:4',
        'prediction_range_min' => 'decimal:2',
        'prediction_range_max' => 'decimal:2',
        'variance' => 'decimal:2',
        'variance_percent' => 'decimal:2',
        'is_accurate' => 'boolean',
        'used_in_quotation' => 'boolean',
        'feedback_at' => 'datetime',
    ];

    // Feedback Status
    const FEEDBACK_PENDING = 'pending';
    const FEEDBACK_CONFIRMED = 'confirmed';
    const FEEDBACK_REJECTED = 'rejected';

    // ===== العلاقات =====

    public function pricingModel()
    {
        return $this->belongsTo(PricingModel::class);
    }

    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function feedbackBy()
    {
        return $this->belongsTo(User::class, 'feedback_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ===== Scopes =====

    public function scopeWithActualPrice($query)
    {
        return $query->whereNotNull('actual_price');
    }

    public function scopeAccurate($query)
    {
        return $query->where('is_accurate', true);
    }

    public function scopePendingFeedback($query)
    {
        return $query->where('feedback_status', self::FEEDBACK_PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('feedback_status', self::FEEDBACK_CONFIRMED);
    }

    public function scopeHighConfidence($query, float $threshold = 0.8)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    // ===== Accessors =====

    public function getFeedbackStatusNameAttribute()
    {
        $statuses = [
            'pending' => 'في الانتظار',
            'confirmed' => 'مؤكد',
            'rejected' => 'مرفوض',
        ];
        return $statuses[$this->feedback_status] ?? $this->feedback_status;
    }

    public function getConfidenceLevelAttribute()
    {
        if ($this->confidence_score >= 0.9) return 'high';
        if ($this->confidence_score >= 0.7) return 'medium';
        return 'low';
    }

    public function getConfidenceLevelNameAttribute()
    {
        $levels = [
            'high' => 'عالي',
            'medium' => 'متوسط',
            'low' => 'منخفض',
        ];
        return $levels[$this->confidence_level] ?? 'غير محدد';
    }

    public function getPriceRangeAttribute()
    {
        if ($this->prediction_range_min && $this->prediction_range_max) {
            return [
                'min' => $this->prediction_range_min,
                'max' => $this->prediction_range_max,
            ];
        }
        return null;
    }

    // ===== Methods =====

    /**
     * تسجيل السعر الفعلي
     */
    public function recordActualPrice(float $actualPrice): self
    {
        $variance = $actualPrice - $this->predicted_price;
        $variancePercent = $this->predicted_price > 0 
            ? ($variance / $this->predicted_price) * 100 
            : 0;

        $this->update([
            'actual_price' => $actualPrice,
            'variance' => $variance,
            'variance_percent' => $variancePercent,
            'is_accurate' => abs($variancePercent) <= 10, // دقيق إذا كان الفرق أقل من 10%
        ]);

        return $this;
    }

    /**
     * تأكيد التنبؤ
     */
    public function confirm(?string $notes = null): self
    {
        $this->update([
            'feedback_status' => self::FEEDBACK_CONFIRMED,
            'feedback_notes' => $notes,
            'feedback_by' => auth()->id(),
            'feedback_at' => now(),
        ]);

        return $this;
    }

    /**
     * رفض التنبؤ
     */
    public function reject(?string $notes = null): self
    {
        $this->update([
            'feedback_status' => self::FEEDBACK_REJECTED,
            'feedback_notes' => $notes,
            'feedback_by' => auth()->id(),
            'feedback_at' => now(),
        ]);

        return $this;
    }

    /**
     * استخدام التنبؤ في عرض سعر
     */
    public function useInQuotation($quotationId): self
    {
        $this->update([
            'used_in_quotation' => true,
            'quotation_id' => $quotationId,
        ]);

        return $this;
    }

    /**
     * الحصول على ملخص التنبؤ
     */
    public function getSummary(): array
    {
        return [
            'predicted_price' => $this->predicted_price,
            'actual_price' => $this->actual_price,
            'variance' => $this->variance,
            'variance_percent' => $this->variance_percent,
            'confidence' => $this->confidence_score,
            'confidence_level' => $this->confidence_level_name,
            'is_accurate' => $this->is_accurate,
            'feedback_status' => $this->feedback_status_name,
            'price_range' => $this->price_range,
        ];
    }
}
