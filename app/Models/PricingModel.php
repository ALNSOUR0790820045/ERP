<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PricingModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'model_type', // linear_regression, random_forest, neural_network, gradient_boosting
        'category', // materials, labor, equipment, subcontractor
        'version',
        'status', // draft, training, active, deprecated
        'features', // الميزات المستخدمة في النموذج
        'hyperparameters',
        'training_data_count',
        'training_start_at',
        'training_end_at',
        'accuracy_score', // R² score
        'mae', // Mean Absolute Error
        'rmse', // Root Mean Square Error
        'mape', // Mean Absolute Percentage Error
        'feature_importance',
        'model_path', // مسار الموديل المحفوظ
        'scaler_path', // مسار المعايير
        'last_prediction_at',
        'predictions_count',
        'auto_retrain',
        'retrain_threshold', // الحد الأدنى للدقة قبل إعادة التدريب
        'retrain_interval_days',
        'next_retrain_at',
        'settings',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'features' => 'array',
        'hyperparameters' => 'array',
        'feature_importance' => 'array',
        'settings' => 'array',
        'metadata' => 'array',
        'training_start_at' => 'datetime',
        'training_end_at' => 'datetime',
        'last_prediction_at' => 'datetime',
        'next_retrain_at' => 'datetime',
        'accuracy_score' => 'decimal:4',
        'mae' => 'decimal:4',
        'rmse' => 'decimal:4',
        'mape' => 'decimal:4',
        'auto_retrain' => 'boolean',
    ];

    // Model Types
    const TYPE_LINEAR = 'linear_regression';
    const TYPE_RANDOM_FOREST = 'random_forest';
    const TYPE_NEURAL = 'neural_network';
    const TYPE_GRADIENT = 'gradient_boosting';
    const TYPE_XGB = 'xgboost';
    const TYPE_ENSEMBLE = 'ensemble';

    // Categories
    const CATEGORY_MATERIALS = 'materials';
    const CATEGORY_LABOR = 'labor';
    const CATEGORY_EQUIPMENT = 'equipment';
    const CATEGORY_SUBCONTRACTOR = 'subcontractor';
    const CATEGORY_GENERAL = 'general';

    // Status
    const STATUS_DRAFT = 'draft';
    const STATUS_TRAINING = 'training';
    const STATUS_ACTIVE = 'active';
    const STATUS_DEPRECATED = 'deprecated';

    // ===== العلاقات =====

    public function predictions()
    {
        return $this->hasMany(PricingPrediction::class);
    }

    public function historicalData()
    {
        return $this->hasMany(HistoricalPricingData::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ===== Scopes =====

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeNeedsRetrain($query)
    {
        return $query->where('auto_retrain', true)
                     ->where(function ($q) {
                         $q->where('next_retrain_at', '<=', now())
                           ->orWhere('accuracy_score', '<', \DB::raw('retrain_threshold'));
                     });
    }

    // ===== Accessors =====

    public function getStatusNameAttribute()
    {
        $statuses = [
            'draft' => 'مسودة',
            'training' => 'قيد التدريب',
            'active' => 'نشط',
            'deprecated' => 'متقادم',
        ];
        return $statuses[$this->status] ?? $this->status;
    }

    public function getCategoryNameAttribute()
    {
        $categories = [
            'materials' => 'المواد',
            'labor' => 'العمالة',
            'equipment' => 'المعدات',
            'subcontractor' => 'مقاولي الباطن',
            'general' => 'عام',
        ];
        return $categories[$this->category] ?? $this->category;
    }

    public function getModelTypeNameAttribute()
    {
        $types = [
            'linear_regression' => 'الانحدار الخطي',
            'random_forest' => 'الغابة العشوائية',
            'neural_network' => 'الشبكة العصبية',
            'gradient_boosting' => 'تعزيز التدرج',
            'xgboost' => 'XGBoost',
            'ensemble' => 'مجمع',
        ];
        return $types[$this->model_type] ?? $this->model_type;
    }

    public function getAccuracyPercentAttribute()
    {
        return round($this->accuracy_score * 100, 2);
    }

    public function getIsAccurateAttribute()
    {
        return $this->accuracy_score >= ($this->retrain_threshold ?? 0.8);
    }

    // ===== Methods =====

    /**
     * تدريب النموذج
     */
    public function train(): array
    {
        $this->update([
            'status' => self::STATUS_TRAINING,
            'training_start_at' => now(),
        ]);

        try {
            // جمع بيانات التدريب
            $trainingData = $this->collectTrainingData();
            
            // تدريب النموذج (يحتاج Python ML service)
            $result = $this->callMLService('train', [
                'model_type' => $this->model_type,
                'features' => $this->features,
                'hyperparameters' => $this->hyperparameters,
                'data' => $trainingData,
            ]);

            $this->update([
                'status' => self::STATUS_ACTIVE,
                'training_end_at' => now(),
                'training_data_count' => count($trainingData),
                'accuracy_score' => $result['accuracy'] ?? 0,
                'mae' => $result['mae'] ?? 0,
                'rmse' => $result['rmse'] ?? 0,
                'mape' => $result['mape'] ?? 0,
                'feature_importance' => $result['feature_importance'] ?? [],
                'model_path' => $result['model_path'] ?? null,
                'scaler_path' => $result['scaler_path'] ?? null,
                'next_retrain_at' => now()->addDays($this->retrain_interval_days ?? 30),
            ]);

            return ['success' => true, 'accuracy' => $result['accuracy']];

        } catch (\Exception $e) {
            $this->update(['status' => self::STATUS_DRAFT]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * جمع بيانات التدريب
     */
    protected function collectTrainingData(): array
    {
        return $this->historicalData()
            ->where('is_valid', true)
            ->get()
            ->map(function ($record) {
                $features = [];
                foreach ($this->features as $feature) {
                    $features[$feature] = data_get($record->features, $feature);
                }
                return [
                    'features' => $features,
                    'target' => $record->actual_price,
                ];
            })
            ->toArray();
    }

    /**
     * التنبؤ بالسعر
     */
    public function predict(array $features): array
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return ['error' => 'النموذج غير نشط'];
        }

        try {
            $result = $this->callMLService('predict', [
                'model_path' => $this->model_path,
                'scaler_path' => $this->scaler_path,
                'features' => $features,
            ]);

            // حفظ التنبؤ
            $prediction = $this->predictions()->create([
                'input_features' => $features,
                'predicted_price' => $result['prediction'],
                'confidence_score' => $result['confidence'] ?? null,
                'prediction_range_min' => $result['range_min'] ?? null,
                'prediction_range_max' => $result['range_max'] ?? null,
            ]);

            $this->update([
                'last_prediction_at' => now(),
            ]);
            $this->increment('predictions_count');

            return [
                'success' => true,
                'prediction' => $result['prediction'],
                'confidence' => $result['confidence'] ?? null,
                'prediction_id' => $prediction->id,
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * استدعاء خدمة ML
     */
    protected function callMLService(string $action, array $data): array
    {
        // هنا يتم الاتصال بخدمة Python ML
        // يمكن استخدام HTTP API أو Queue
        
        // Placeholder - يحتاج تنفيذ فعلي
        return [
            'accuracy' => 0.85,
            'mae' => 0.05,
            'rmse' => 0.08,
            'mape' => 5.2,
            'feature_importance' => [],
            'prediction' => 0,
            'confidence' => 0.9,
        ];
    }

    /**
     * تقييم أداء النموذج
     */
    public function evaluate(): array
    {
        $predictions = $this->predictions()
            ->whereNotNull('actual_price')
            ->get();

        if ($predictions->count() < 10) {
            return ['error' => 'بيانات غير كافية للتقييم'];
        }

        $errors = $predictions->map(function ($p) {
            return abs($p->predicted_price - $p->actual_price);
        });

        $percentErrors = $predictions->map(function ($p) {
            if ($p->actual_price == 0) return 0;
            return abs(($p->predicted_price - $p->actual_price) / $p->actual_price) * 100;
        });

        return [
            'total_predictions' => $predictions->count(),
            'mae' => $errors->avg(),
            'mape' => $percentErrors->avg(),
            'accuracy_within_5' => $percentErrors->filter(fn($e) => $e <= 5)->count() / $predictions->count() * 100,
            'accuracy_within_10' => $percentErrors->filter(fn($e) => $e <= 10)->count() / $predictions->count() * 100,
        ];
    }
}
