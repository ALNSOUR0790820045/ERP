<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistoricalPricingData extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'historical_pricing_data';

    protected $fillable = [
        'pricing_model_id',
        'source_type', // purchase_order, invoice, quotation, contract
        'source_id',
        'item_type', // material, labor, equipment
        'item_id',
        'item_code',
        'item_name',
        'category',
        'subcategory',
        'features', // الميزات المستخدمة للتدريب
        'actual_price',
        'unit',
        'quantity',
        'total_amount',
        'supplier_id',
        'supplier_name',
        'project_id',
        'project_type',
        'location',
        'region',
        'transaction_date',
        'currency',
        'exchange_rate',
        'is_valid',
        'validation_errors',
        'normalized_price', // السعر بعد التطبيع
        'outlier_score', // درجة الشذوذ
        'is_outlier',
        'used_for_training',
        'training_weight', // وزن في التدريب
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'features' => 'array',
        'validation_errors' => 'array',
        'metadata' => 'array',
        'actual_price' => 'decimal:4',
        'quantity' => 'decimal:4',
        'total_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'normalized_price' => 'decimal:4',
        'outlier_score' => 'decimal:4',
        'training_weight' => 'decimal:4',
        'transaction_date' => 'date',
        'is_valid' => 'boolean',
        'is_outlier' => 'boolean',
        'used_for_training' => 'boolean',
    ];

    // Source Types
    const SOURCE_PURCHASE_ORDER = 'purchase_order';
    const SOURCE_INVOICE = 'invoice';
    const SOURCE_QUOTATION = 'quotation';
    const SOURCE_CONTRACT = 'contract';
    const SOURCE_MARKET = 'market';
    const SOURCE_MANUAL = 'manual';

    // Item Types
    const ITEM_MATERIAL = 'material';
    const ITEM_LABOR = 'labor';
    const ITEM_EQUIPMENT = 'equipment';
    const ITEM_SERVICE = 'service';

    // ===== العلاقات =====

    public function pricingModel()
    {
        return $this->belongsTo(PricingModel::class);
    }

    public function source()
    {
        return $this->morphTo('source', 'source_type', 'source_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ===== Scopes =====

    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    public function scopeNotOutlier($query)
    {
        return $query->where('is_outlier', false);
    }

    public function scopeForTraining($query)
    {
        return $query->valid()->notOutlier();
    }

    public function scopeByItemType($query, string $type)
    {
        return $query->where('item_type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeRecent($query, int $months = 12)
    {
        return $query->where('transaction_date', '>=', now()->subMonths($months));
    }

    // ===== Accessors =====

    public function getSourceTypeNameAttribute()
    {
        $types = [
            'purchase_order' => 'أمر شراء',
            'invoice' => 'فاتورة',
            'quotation' => 'عرض سعر',
            'contract' => 'عقد',
            'market' => 'سعر سوق',
            'manual' => 'إدخال يدوي',
        ];
        return $types[$this->source_type] ?? $this->source_type;
    }

    public function getItemTypeNameAttribute()
    {
        $types = [
            'material' => 'مادة',
            'labor' => 'عمالة',
            'equipment' => 'معدات',
            'service' => 'خدمة',
        ];
        return $types[$this->item_type] ?? $this->item_type;
    }

    public function getUnitPriceAttribute()
    {
        if ($this->quantity > 0) {
            return $this->total_amount / $this->quantity;
        }
        return $this->actual_price;
    }

    // ===== Methods =====

    /**
     * التحقق من صحة البيانات
     */
    public function validate(): bool
    {
        $errors = [];

        if (empty($this->actual_price) || $this->actual_price <= 0) {
            $errors[] = 'السعر غير صالح';
        }

        if (empty($this->quantity) || $this->quantity <= 0) {
            $errors[] = 'الكمية غير صالحة';
        }

        if (empty($this->transaction_date)) {
            $errors[] = 'تاريخ العملية مطلوب';
        }

        if (empty($this->item_code) && empty($this->item_name)) {
            $errors[] = 'يجب تحديد العنصر';
        }

        $this->update([
            'is_valid' => empty($errors),
            'validation_errors' => $errors ?: null,
        ]);

        return empty($errors);
    }

    /**
     * تطبيع السعر
     */
    public function normalize(): float
    {
        $normalizedPrice = $this->actual_price;

        // تحويل العملة إذا لزم الأمر
        if ($this->currency !== 'JOD' && $this->exchange_rate > 0) {
            $normalizedPrice *= $this->exchange_rate;
        }

        // تعديل حسب التضخم (اختياري)
        // $normalizedPrice = $this->adjustForInflation($normalizedPrice);

        $this->update(['normalized_price' => $normalizedPrice]);

        return $normalizedPrice;
    }

    /**
     * كشف القيم الشاذة
     */
    public function detectOutlier(float $mean, float $stdDev, float $threshold = 3): bool
    {
        if ($stdDev == 0) {
            $this->update(['outlier_score' => 0, 'is_outlier' => false]);
            return false;
        }

        $zScore = abs(($this->normalized_price ?? $this->actual_price) - $mean) / $stdDev;
        $isOutlier = $zScore > $threshold;

        $this->update([
            'outlier_score' => $zScore,
            'is_outlier' => $isOutlier,
        ]);

        return $isOutlier;
    }

    /**
     * بناء الميزات للتدريب
     */
    public function buildFeatures(): array
    {
        $features = [
            'category' => $this->category,
            'subcategory' => $this->subcategory,
            'region' => $this->region,
            'project_type' => $this->project_type,
            'supplier_id' => $this->supplier_id,
            'quantity' => $this->quantity,
            'month' => $this->transaction_date?->month,
            'year' => $this->transaction_date?->year,
            'quarter' => $this->transaction_date?->quarter,
        ];

        $this->update(['features' => $features]);

        return $features;
    }

    /**
     * تحديث وزن التدريب
     */
    public function updateTrainingWeight(): float
    {
        // البيانات الأحدث لها وزن أعلى
        $monthsAgo = $this->transaction_date?->diffInMonths(now()) ?? 12;
        $timeWeight = max(0.1, 1 - ($monthsAgo / 24)); // تناقص على مدى سنتين

        // البيانات من مصادر موثوقة لها وزن أعلى
        $sourceWeight = match($this->source_type) {
            'contract' => 1.0,
            'invoice' => 0.9,
            'purchase_order' => 0.8,
            'quotation' => 0.6,
            default => 0.5,
        };

        $weight = $timeWeight * $sourceWeight;

        $this->update(['training_weight' => $weight]);

        return $weight;
    }

    /**
     * استيراد بيانات تاريخية
     */
    public static function importFromSource(string $sourceType, $records): array
    {
        $imported = 0;
        $errors = [];

        foreach ($records as $record) {
            try {
                $data = static::create([
                    'source_type' => $sourceType,
                    'source_id' => $record['id'] ?? null,
                    'item_type' => $record['item_type'] ?? 'material',
                    'item_id' => $record['item_id'] ?? null,
                    'item_code' => $record['item_code'] ?? null,
                    'item_name' => $record['item_name'] ?? null,
                    'category' => $record['category'] ?? null,
                    'actual_price' => $record['price'] ?? 0,
                    'unit' => $record['unit'] ?? null,
                    'quantity' => $record['quantity'] ?? 1,
                    'total_amount' => $record['total'] ?? ($record['price'] * ($record['quantity'] ?? 1)),
                    'supplier_id' => $record['supplier_id'] ?? null,
                    'project_id' => $record['project_id'] ?? null,
                    'transaction_date' => $record['date'] ?? now(),
                    'currency' => $record['currency'] ?? 'JOD',
                    'created_by' => auth()->id(),
                ]);

                $data->validate();
                $data->normalize();
                $data->buildFeatures();
                $data->updateTrainingWeight();

                $imported++;

            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return [
            'imported' => $imported,
            'errors' => $errors,
        ];
    }
}
