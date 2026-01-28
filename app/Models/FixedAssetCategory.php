<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAssetCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'parent_id',
        'depreciation_method',
        'useful_life_years',
        'salvage_value_percent',
        'asset_account_id',
        'depreciation_account_id',
        'accumulated_depreciation_account_id',
        'is_active',
    ];

    protected $casts = [
        'useful_life_years' => 'decimal:2',
        'salvage_value_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function parent(): BelongsTo
    {
        return $this->belongsTo(FixedAssetCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(FixedAssetCategory::class, 'parent_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(FixedAsset::class, 'category_id');
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'asset_account_id');
    }

    public function depreciationAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'depreciation_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'accumulated_depreciation_account_id');
    }

    // الثوابت
    public const DEPRECIATION_METHODS = [
        'straight_line' => 'القسط الثابت',
        'declining_balance' => 'القسط المتناقص',
        'units_of_production' => 'وحدات الإنتاج',
    ];

    public function getDepreciationMethodLabelAttribute(): string
    {
        return self::DEPRECIATION_METHODS[$this->depreciation_method] ?? $this->depreciation_method;
    }
}
