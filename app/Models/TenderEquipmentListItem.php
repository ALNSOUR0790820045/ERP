<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * قائمة المعدات للعطاء
 * Tender Equipment List - نموذج المعدات من نماذج العرض
 */
class TenderEquipmentListItem extends Model
{
    protected $table = 'tender_equipment_list';

    protected $fillable = [
        'technical_proposal_id',
        'equipment_type',
        'make_model',
        'quantity',
        'year_of_manufacture',
        'condition',
        'capacity',
        'capacity_unit',
        'ownership',
        'current_location',
        'ownership_proof_path',
        'registration_path',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'year_of_manufacture' => 'integer',
        'capacity' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    // أنواع الملكية
    public const OWNERSHIP_TYPES = [
        'owned' => 'ملك',
        'leased' => 'إيجار حالي',
        'to_purchase' => 'سيتم شراؤها',
        'to_lease' => 'سيتم استئجارها',
    ];

    // حالة المعدة
    public const CONDITIONS = [
        'excellent' => 'ممتازة',
        'good' => 'جيدة',
        'fair' => 'مقبولة',
        'poor' => 'ضعيفة',
    ];

    public function technicalProposal(): BelongsTo
    {
        return $this->belongsTo(TenderTechnicalProposal::class);
    }

    public function getOwnershipNameAttribute(): string
    {
        return self::OWNERSHIP_TYPES[$this->ownership] ?? $this->ownership;
    }

    public function getConditionNameAttribute(): string
    {
        return self::CONDITIONS[$this->condition] ?? $this->condition;
    }

    /**
     * عمر المعدة بالسنوات
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->year_of_manufacture) {
            return null;
        }
        return now()->year - $this->year_of_manufacture;
    }
}
