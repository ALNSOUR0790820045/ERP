<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAsset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_number',
        'name_ar',
        'name_en',
        'description',
        'category_id',
        'branch_id',
        'department_id',
        'project_id',
        'employee_id',
        'acquisition_date',
        'acquisition_cost',
        'currency_id',
        'supplier_name',
        'invoice_number',
        'serial_number',
        'model',
        'manufacturer',
        'location',
        'status',
        'in_service_date',
        'useful_life_years',
        'salvage_value',
        'depreciation_method',
        'accumulated_depreciation',
        'book_value',
        'last_depreciation_date',
        'disposal_date',
        'disposal_amount',
        'disposal_reason',
        'warranty_period',
        'warranty_expiry',
        'insurance_policy',
        'insurance_expiry',
        'custom_fields',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'acquisition_cost' => 'decimal:3',
        'useful_life_years' => 'decimal:2',
        'salvage_value' => 'decimal:3',
        'accumulated_depreciation' => 'decimal:3',
        'book_value' => 'decimal:3',
        'last_depreciation_date' => 'date',
        'in_service_date' => 'date',
        'disposal_date' => 'date',
        'disposal_amount' => 'decimal:3',
        'warranty_expiry' => 'date',
        'insurance_expiry' => 'date',
        'custom_fields' => 'array',
    ];

    // العلاقات
    public function category(): BelongsTo
    {
        return $this->belongsTo(FixedAssetCategory::class, 'category_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(FixedAssetDepreciation::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(FixedAssetMaintenance::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // الثوابت
    public const STATUSES = [
        'active' => 'نشط',
        'under_maintenance' => 'تحت الصيانة',
        'disposed' => 'مستبعد',
        'sold' => 'مباع',
    ];

    public const DEPRECIATION_METHODS = [
        'straight_line' => 'القسط الثابت',
        'declining_balance' => 'القسط المتناقص',
        'units_of_production' => 'وحدات الإنتاج',
    ];

    // الدوال المساعدة
    public function calculateMonthlyDepreciation(): float
    {
        if ($this->depreciation_method === 'straight_line') {
            $depreciableAmount = $this->acquisition_cost - $this->salvage_value;
            $monthlyDepreciation = $depreciableAmount / ($this->useful_life_years * 12);
            return round($monthlyDepreciation, 3);
        }
        
        // للطرق الأخرى
        return 0;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
